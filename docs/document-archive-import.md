# Quy trình hoàn chỉnh nhập kho văn bản cũ

> Với chức năng quản lý sổ theo năm mới, xem [document-ledger.md](document-ledger.md) để chạy đúng các lệnh nhập file, nhập/cập nhật sổ đến và hai sheet sổ đi từ đường dẫn `D:\Agribank văn bản\Văn Thư`. Lệnh `documents:import-ledger` mới vào sổ cả văn bản không có file; `documents:enrich-from-ledger` cũ chỉ bổ sung metadata.

Tài liệu này dùng cho kho chung chứa cả văn bản đến và văn bản đi. Luồng nhập ưu tiên dữ liệu theo thứ tự:

1. Tên file -> `document_code` (số, ký hiệu văn bản).
2. Folder `NGAY dd-mm-yyyy` -> ngày đến của văn bản đến hoặc ngày chuyển của văn bản đi khi sổ không cung cấp ngày đó.
3. Hai sổ Excel -> phân loại và bổ sung các trường nghiệp vụ; ngày văn bản và trích yếu ưu tiên dữ liệu trong sổ. Nếu đã vào sổ trước khi gắn file, ngày văn bản/trích yếu có nội dung trong Excel được cập nhật vào văn bản đó; ô trống không xóa dữ liệu cũ.
4. PDF -> chỉ bù `issued_date` (ngày văn bản) và `title` (trích yếu) còn thiếu bằng `pdftotext` hoặc Tesseract OCR.

File không khớp sổ hoặc khớp mơ hồ **vẫn được nhập** với trạng thái `unclassified` (Chưa phân loại). OCR không quyết định văn bản đến hay đi và việc nhập kho cũ không tạo thông báo “văn bản mới”.

**Quy ước ngày khi không khớp sổ:** lấy ngày thư mục làm `issued_date` (ngày văn bản). Ví dụ `NAM 2026\THANG 01-2026\NGAY 05-01-2026` → lưu `2026-01-05`, giao diện hiển thị **05/01/2026**. Áp dụng cả khi không cung cấp Excel hoặc có nhiều dòng khớp không thể chọn chắc chắn; không bịa trích yếu từ tên file. Đây là ngày quy ước của kho, chưa chắc là ngày ban hành thực tế.

Không cần thêm tham số để bật quy tắc này. Bảng `--dry-run` có cả **Ngày kho** và **Ngày văn bản** để kiểm tra trước. Nếu khớp sổ nhưng sổ thiếu ngày văn bản, giữ quy tắc trích xuất/OCR bổ sung như cũ; ngày có trong sổ không bị ngày folder ghi đè, kể cả dùng `--date-field=both`.

**Thư mục ngày thiếu năm cũng được nhận:** `NAM 2022\THANG 12-2022\NGAY 06-12` → **06/12/2022**. Năm lấy từ thư mục cha `THANG mm-yyyy` hoặc `NAM yyyy` gần nhất, hỗ trợ cả tên có dấu `NĂM/THÁNG/NGÀY` và ngày/tháng một chữ số. Nếu thư mục ngày ghi đủ năm thì giữ năm đó. Không có thư mục cha nhận diện được năm hoặc ngày không hợp lệ (ví dụ 29/02/2022) thì vẫn báo không xác định được ngày; không tự gán năm hiện tại. Lệnh import giữ nguyên, không cần thêm tham số.

File đã nhập trước vẫn báo **Trùng** và bị bỏ qua; chạy lại import không tự sửa ngày cho những bản ghi cũ. Không xóa file/database để ép nhập lại. Thư mục không xác định được ngày vẫn bị bỏ qua, trừ khi chủ động dùng `--fallback-mtime` (khi đó ngày dự phòng là ngày sửa file).

## Các đường dẫn dùng trong ví dụ

- Source Laravel trên server: `D:\hotrobieumau`
- Kho văn bản: `Z:\KHO VAN BAN CHUNG`
- Sổ đến: `E:\linh tinh\Văn Thư\Sổ vb đến 2025 (01-01-2026).xlsx`
- Sổ đi: `E:\linh tinh\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx`
- ID văn thư: `3`

Nếu source hoặc file trên server nằm ở đường dẫn khác thì thay đường dẫn tương ứng trong các lệnh dưới đây. Nên dùng đúng một cửa sổ PowerShell chạy dưới tài khoản Windows có quyền đọc ổ `Z:` và ghi vào `storage` của Laravel.

## Bước 1: kiểm tra trước khi nhập

Mở PowerShell trên **máy server** rồi vào source Laravel:

```powershell
Set-Location "D:\hotrobieumau"
```

Kiểm tra đủ ba nguồn dữ liệu. Cả ba lệnh phải trả về `True`:

```powershell
Test-Path "Z:\KHO VAN BAN CHUNG"
Test-Path "E:\linh tinh\Văn Thư\Sổ vb đến 2025 (01-01-2026).xlsx"
Test-Path "E:\linh tinh\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx"
```

Nếu `Z:` là ổ mạng và PowerShell chạy Administrator trả về `False`, hãy dùng đường dẫn UNC dạng `\\ten-may\thu-muc` hoặc map lại ổ trong chính cửa sổ PowerShell đó.

Kiểm tra Laravel và database:

```powershell
php -v
php artisan about
php artisan optimize:clear
php artisan migrate --force
php artisan storage:link
```

`storage:link` chỉ cần tạo một lần. Nếu Laravel báo liên kết đã tồn tại thì tiếp tục bình thường.

Nếu lệnh `php` không được nhận, dùng PHP của XAMPP, ví dụ:

```powershell
& "D:\xampp\php\php.exe" artisan about
```

### Nếu `--unmatched=unclassified` bị từ chối

Thông báo `--unmatched chỉ nhận skip, incoming hoặc outgoing` nghĩa là máy server vẫn đang chạy phiên bản importer cũ. Không dùng `--unmatched=incoming` hoặc `--unmatched=outgoing` để thay thế vì sẽ phân loại sai các file không khớp sổ.

Đồng bộ source code mới từ máy làm việc sang máy server nhưng **không ghi đè** `.env`, thư mục `storage` hoặc liên kết `public/storage`. Sau đó vào đúng thư mục Laravel trên server rồi chạy:

```powershell
Get-Location
Select-String -Path ".\app\Console\Commands\ImportDocumentArchive.php" -Pattern "unmatched=unclassified"
php artisan optimize:clear
php artisan help documents:import-archive
```

Kết quả trợ giúp phải chứa:

```text
--unmatched ... unclassified, skip, incoming hoặc outgoing [default: "unclassified"]
```

Nếu copy file thủ công thay vì đồng bộ cả source, tối thiểu phải chép đồng thời các file sau:

```text
app/Console/Commands/ImportDocumentArchive.php
app/Models/Document.php
app/Services/DocumentService.php
app/Http/Controllers/User/DocumentApiController.php
app/Http/Controllers/User/DocumentController.php
public/js/user/document-list.js
resources/views/user/page/document_detail.blade.php
resources/views/user/page/document_report.blade.php
```

Thay đổi `unclassified` không cần chạy migration mới. Sau khi phần trợ giúp hiển thị đúng option mới, tiếp tục bước 2.

## Bước 2: chạy thử toàn bộ kho

Lệnh này chỉ quét, đọc hai sổ và in thống kê; chưa sao chép file, chưa ghi database:

```powershell
php artisan documents:import-archive "Z:\KHO VAN BAN CHUNG" --user=3 --direction=auto --incoming-ledger="E:\linh tinh\Văn Thư\Sổ vb đến 2025 (01-01-2026).xlsx" --outgoing-ledger="E:\linh tinh\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx" --unmatched=unclassified --dry-run
```

Kiểm tra các cột:

- `Có thể nhập`: tổng file hợp lệ sẽ được nhập.
- `Đến`, `Đi`: số file phân loại được bằng sổ.
- `Chưa phân loại`: file vẫn được nhập nhưng chưa xác định chắc loại.
- `Không có ngày`: file không nằm dưới folder `NGAY dd-mm-yyyy`.
- `Lỗi`: phải xem và xử lý trước khi nhập thật nếu số này khác 0.

## Bước 3: nhập thật

Khi bảng chạy thử hợp lý, chạy:

```powershell
php artisan documents:import-archive "Z:\KHO VAN BAN CHUNG" --user=3 --direction=auto --incoming-ledger="E:\linh tinh\Văn Thư\Sổ vb đến 2025 (01-01-2026).xlsx" --outgoing-ledger="E:\linh tinh\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx" --unmatched=unclassified --yes
```

Có thể chạy lại đúng lệnh nếu PowerShell bị đóng hoặc import bị gián đoạn. Importer nhận diện file đã nhập và ghi vào cột `Trùng`, không tạo thêm bản sao.

## Bước 4: xử lý file không có folder ngày

Nếu thống kê ở bước 2 có `Không có ngày` lớn hơn 0, importer mặc định bỏ qua các file đó để không gán sai ngày. Muốn dùng ngày sửa file làm ngày kho, phải chạy thử lượt hai:

```powershell
php artisan documents:import-archive "Z:\KHO VAN BAN CHUNG" --user=3 --direction=auto --incoming-ledger="E:\linh tinh\Văn Thư\Sổ vb đến 2025 (01-01-2026).xlsx" --outgoing-ledger="E:\linh tinh\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx" --unmatched=unclassified --fallback-mtime --dry-run
```

Chỉ khi ngày xem trước hợp lý mới nhập thật lượt vét:

```powershell
php artisan documents:import-archive "Z:\KHO VAN BAN CHUNG" --user=3 --direction=auto --incoming-ledger="E:\linh tinh\Văn Thư\Sổ vb đến 2025 (01-01-2026).xlsx" --outgoing-ledger="E:\linh tinh\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx" --unmatched=unclassified --fallback-mtime --yes
```

Lượt này quét lại cả kho nhưng tự bỏ qua các file đã nhập. Không dùng `--fallback-mtime` nếu ngày sửa file là ngày vừa copy dữ liệu sang server.

## Bước 5: cấu hình OCR trên server

Không có Poppler/Tesseract thì toàn bộ bước import phía trên vẫn hoàn thành; chỉ chưa tự bù ngày văn bản và trích yếu. Có thể cài OCR sau rồi tiếp tục từ bước này.

Server cần:

- Poppler có `pdftotext.exe` và `pdftoppm.exe`.
- Tesseract OCR có `eng.traineddata` và `vie.traineddata` trong thư mục `tessdata`.

Khai báo đường dẫn tuyệt đối trong `.env` trên server (đường dẫn có khoảng trắng phải đặt trong dấu nháy):

```dotenv
DOCUMENT_PDFTOTEXT_BINARY=C:/Tools/poppler/Library/bin/pdftotext.exe
DOCUMENT_PDFTOPPM_BINARY=C:/Tools/poppler/Library/bin/pdftoppm.exe
DOCUMENT_TESSERACT_BINARY="C:/Program Files/Tesseract-OCR/tesseract.exe"
DOCUMENT_TESSERACT_LANGUAGES=vie+eng
DOCUMENT_OCR_MAX_PAGES=2
DOCUMENT_OCR_DPI=220
DOCUMENT_OCR_TIMEOUT_SECONDS=150
```

Nạp lại cấu hình và kiểm tra binary:

```powershell
php artisan optimize:clear
& "C:\Tools\poppler\Library\bin\pdftotext.exe" -v
& "C:\Tools\poppler\Library\bin\pdftoppm.exe" -v
& "C:\Program Files\Tesseract-OCR\tesseract.exe" --version
& "C:\Program Files\Tesseract-OCR\tesseract.exe" --list-langs
php artisan documents:queue-metadata-extraction --dry-run
```

Kết quả `--list-langs` phải có `eng` và `vie`. Lệnh Laravel phải báo sẵn sàng đọc lớp chữ PDF và OCR ảnh bằng Tesseract.

## Bước 6: chạy OCR cho toàn bộ PDF còn thiếu metadata

Đưa các PDF thuộc kho cũ còn thiếu `issued_date` hoặc `title` vào queue:

```powershell
php artisan documents:queue-metadata-extraction
```

Xử lý hết queue rồi tự dừng:

```powershell
php artisan queue:work --queue=document-ocr --tries=2 --timeout=180 --memory=256 --stop-when-empty
```

Kiểm tra job lỗi và số PDF vẫn còn thiếu metadata:

```powershell
php artisan queue:failed
php artisan documents:queue-metadata-extraction --dry-run
```

PDF không nhận dạng được hoặc không có mẫu `Ngày:`/`Nội dung:` phù hợp có thể vẫn còn trong danh sách thiếu metadata; file và bản ghi văn bản của nó không bị xóa. OCR không ghi đè ngày hoặc trích yếu đã có từ sổ Excel.

## Bước 7: hoàn tất

Tạo lại cache production sau khi import và cấu hình OCR xong:

```powershell
php artisan optimize
```

Mở trang danh sách văn bản để kiểm tra:

- Số, ký hiệu mở đúng file đính kèm.
- Văn bản khớp sổ hiển thị đúng Đến/Đi.
- File không khớp hiển thị Chưa phân loại.
- Ngày văn bản và trích yếu được bổ sung khi OCR nhận dạng thành công.

## Kho đã nhập bằng phiên bản importer cũ

Nếu dữ liệu cũ từng bị mặc định thành `incoming`, dùng lệnh đối chiếu lại sổ đi trước:

```powershell
php artisan documents:enrich-from-ledger "E:\linh tinh\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx" --direction=outgoing --user=3 --reclassify --dry-run
```

Kết quả đúng thì cập nhật thật:

```powershell
php artisan documents:enrich-from-ledger "E:\linh tinh\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx" --direction=outgoing --user=3 --reclassify --yes
```

Không dùng `--overwrite` trong lần chạy thông thường. Tùy chọn đó chỉ dành cho trường hợp chủ động muốn lấy sổ Excel ghi đè dữ liệu đã sửa tay.

1. Vào source và khai báo đường dẫn
Set-Location "D:\hotrobieumau"

$kho = 'Z:\KHO VAN BAN CHUNG'
$soDen = 'D:\Agribank văn bản\Văn Thư\Sổ vb đến 2026 (01-01-2026).xlsx'
$soDi = 'D:\Agribank văn bản\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx'

Test-Path -LiteralPath $kho
Test-Path -LiteralPath $soDen
Test-Path -LiteralPath $soDi

php artisan optimize:clear
Đổi $kho thành đường dẫn kho thực tế, ví dụ D:\van ban. Cả ba lệnh Test-Path phải trả về True. Máy server cần có source mới và MySQL đang chạy.
2. Chạy thử — chưa lưu dữ liệu
php artisan documents:import-archive "$kho" --user=3 --direction=auto --incoming-ledger="$soDen" --incoming-sheet="CVĐ" --outgoing-ledger="$soDi" --outgoing-sheet="VB đi sau KT" --outgoing-sheet="VB QUYET DINH" --unmatched=unclassified --dry-run
Đọc bảng thống kê, đặc biệt Ngày văn bản, Không có ngày, Trùng và Lỗi. --user=3 là tài khoản văn thư đứng tên nhập; đổi nếu ID thực tế khác.
3. Kiểm tra ổn rồi mới nhập thật
php artisan documents:import-archive "$kho" --user=3 --direction=auto --incoming-ledger="$soDen" --incoming-sheet="CVĐ" --outgoing-ledger="$soDi" --outgoing-sheet="VB đi sau KT" --outgoing-sheet="VB QUYET DINH" --unmatched=unclassified --yes
Lệnh này sẽ:
- Khớp sổ → lấy thông tin từ Excel.
- Không khớp → lưu Chưa phân loại, lấy ngày thư mục làm ngày văn bản.
- Không cần OCR và không thông báo văn bản mới.
- File đã nhập báo Trùng → bỏ qua, không tự cập nhật ngày cũ.
Chưa thêm --fallback-mtime nhé: chỉ dùng khi mày chấp nhận lấy ngày sửa file cho những thư mục không xác định được ngày.
