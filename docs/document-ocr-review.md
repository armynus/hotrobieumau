# Quét PDF và xuất Excel kiểm tra

Lệnh `ocr:export-excel` chỉ đọc dữ liệu và xuất Excel để kiểm tra. Để đồng bộ tự động không cần đánh `OK` từng dòng, dùng `ocr:sync-from-pdf`; lệnh này áp dụng quy tắc bảo vệ dữ liệu bên dưới và ghi nhật ký thay đổi.

## Phạm vi

- Chọn văn bản thiếu `issued_date`, trích yếu `title` trống, hoặc chưa phân loại (`direction` trống/`unclassified`). Văn bản đã đủ ngày và trích yếu vẫn được quét nếu chưa phân loại; `--id` cũng áp dụng quy tắc này.
- Đọc bốn mục trên phiếu trình: số/ký hiệu, ngày văn bản, nơi gởi, nội dung trích yếu.
- Đề xuất phân loại văn bản đến/đi/quyết định theo tiêu đề, số/ký hiệu và cơ quan ban hành/nơi nhận ở phần đầu PDF. Cột căn cứ nêu dấu hiệu đã dùng; nếu không đủ dấu hiệu, để “Chưa đủ căn cứ”.
- Ưu tiên trang phiếu trình đầu tiên. Ngày trong ô `Ngày` là ngày văn bản; không dùng ngày lập phiếu phía trên khi ô này không đọc được.
- Khi nhận ra phiếu, OCR riêng cột nội dung để giảm lẫn chữ viết tay của cột ý kiến. PDF không có phiếu được đọc từ trang đầu và gắn lưu ý cần đối chiếu.
- Với văn bản như giấy mời có mục đánh số `5/. Nội dung:`, tool gom các gạch đầu dòng và dòng xuống hàng thành trích yếu, dừng trước mục `Lưu ý` hoặc mục đánh số kế tiếp; Excel ghi rõ nguồn này để người kiểm tra đối chiếu.
- Chỉ xét tối đa hai trang đầu mặc định. Có thể cấu hình 1–5 trang bằng `DOCUMENT_REVIEW_MAX_PAGES`.
- Nếu một văn bản có nhiều PDF, Excel có một dòng cho mỗi PDF, không tự gộp thông tin giữa các tệp.

## Chuẩn bị máy chạy

Cần PHP của dự án, Node.js >= 20, Poppler có `pdftoppm`. `pdftotext` là tùy chọn giúp đọc PDF có lớp chữ. Tesseract.js chạy nội bộ bằng Node, không cần cài Tesseract.exe. Máy production phải có `node.exe`; không cần cài npm trên production nếu đã chép sẵn `node_modules` tương thích.

```powershell
Set-Location D:\hotrobieumau
npm.cmd ci
powershell -ExecutionPolicy Bypass -File scripts\documents\setup-ocr-models.ps1
```

Hai lệnh chuẩn bị trên dành cho máy dev có internet. `npm.cmd ci` cần Node/npm và tải thư viện; script tải bộ tiếng Việt/Anh từ kho chính thức `tesseract-ocr/tessdata_fast` rồi kiểm tra SHA256. Khi quét, tool chạy OCR cục bộ và không gửi PDF ra ngoài.

### Production không có internet

Trên máy dev có internet, chạy hai lệnh chuẩn bị ở trên một lần. Sau đó chép sang production qua thư mục chia sẻ nội bộ hoặc phương tiện được phép:

- Toàn bộ `node_modules` từ đúng bản code đã triển khai.
- Hai model `storage/app/private/ocr-models/vie.traineddata` và `eng.traineddata`.
- Node.js >= 20 cho Windows (cài từ bộ cài offline hoặc bản runtime portable được tổ chức phê duyệt). Production cần `node.exe` để chạy OCR; không cần `npm ci` ở đó.

Nếu chỉ chép hai model sang một thư mục trung chuyển, ví dụ `E:\ocr-models`, có thể dùng script để sao chép và kiểm tra checksum trên production:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\documents\setup-ocr-models.ps1 -SourcePath E:\ocr-models
```

Thư mục nguồn phải chứa `vie.traineddata` và `eng.traineddata`. Tùy chọn `-SourcePath` không truy cập internet. Có thể đặt `DOCUMENT_REVIEW_MODELS_PATH` trỏ thẳng đến thư mục model đã chép nếu không muốn đặt trong `storage`.

Nếu Poppler/Node chưa nằm trong PATH, cấu hình đường dẫn thực tế trong `.env`:

```dotenv
DOCUMENT_PDFTOPPM_BINARY=C:/Tools/poppler/Library/bin/pdftoppm.exe
DOCUMENT_PDFTOTEXT_BINARY=C:/Tools/poppler/Library/bin/pdftotext.exe
DOCUMENT_REVIEW_NODE_BINARY="C:/Program Files/nodejs/node.exe"
DOCUMENT_REVIEW_DPI=220
DOCUMENT_REVIEW_MAX_PAGES=2
DOCUMENT_REVIEW_TIMEOUT_SECONDS=180
```

Chỉ đặt `DOCUMENT_PDFTOTEXT_BINARY` nếu có file đó. Khi ứng dụng đang cache config, cần cập nhật cache cấu hình theo quy trình triển khai hiện hành. Thư mục model mặc định là `storage/app/private/ocr-models`; có thể đổi bằng `DOCUMENT_REVIEW_MODELS_PATH`.

## Chạy

```powershell
# Thử 50 văn bản thiếu; tên file tự tạo theo thời điểm chạy.
php artisan ocr:export-excel

# Quét tất cả văn bản thiếu ngày/trích yếu hoặc chưa phân loại.
php artisan ocr:export-excel --limit=0

# Giới hạn phạm vi, không làm thay đổi quy tắc bỏ qua văn bản đã đủ dữ liệu.
php artisan ocr:export-excel --branch=1 --limit=50
php artisan ocr:export-excel --id=3 --id=7
php artisan ocr:export-excel --after-id=100 --limit=50

# Chọn tên file; đường dẫn tính từ storage/app/private.
php artisan ocr:export-excel --limit=50 --file=ocr-review/dot-1.xlsx
```

Tool báo tiến độ từng văn bản và đường dẫn Excel khi xong. Không ghi đè file kết quả cũ; chọn tên mới cho đợt tiếp theo.

Kết quả OCR được lưu theo SHA256 của PDF trong `storage/app/private/ocr-review-cache`. Chạy lại tận dụng kết quả đọc đã có; bộ tách trường chạy lại từ chữ lưu đệm. Mỗi dòng cũng được ghi ngay vào file `.xlsx.jsonl` bên cạnh kết quả để giữ tiến độ khi bị ngắt. Sau khi bị ngắt, chạy lại với **tên đầu ra mới**.

## Kiểm tra Excel

- Sheet **Kiem tra PDF**: bốn trường đọc được, trường còn thiếu, tình trạng quét, lưu ý, dữ liệu đang lưu, nguồn PDF và cột vàng **Kiểm tra của bạn**.
- Sheet **Chu doc tu PDF**: chữ OCR gốc, trang/vùng đọc, đường dẫn và SHA256 để đối chiếu.
- Ngày là ô ngày Excel, hiển thị `dd/mm/yyyy`. Số/ký hiệu và nội dung luôn được lưu dạng chữ, không chạy như công thức.
- Link `Mở PDF` dùng đường dẫn có kiểm tra quyền của ứng dụng; cần `APP_URL` đúng và đăng nhập bằng tài khoản có quyền xem.
- `Có đề xuất; cần đối chiếu` chỉ nghĩa là có giá trị đọc ra, **không phải chứng nhận OCR đúng**. Bản scan mờ, dấu tiếng Việt, chữ viết tay và bố cục khác mẫu vẫn cần rà lại.
- Ngày/trích yếu đã có được giữ nguyên trong cột hiện tại. Giá trị đọc khác chỉ là thông tin đối chiếu.

## Tự đồng bộ database không cần duyệt từng dòng

Lệnh `ocr:sync-from-pdf` xử lý theo lô, mặc định tối đa 100 văn bản mỗi lần. Nó quét tất cả PDF của văn bản trong phạm vi, kể cả bản ghi đã đủ ngày và trích yếu nhưng chưa phân loại, vì ngày và nơi gởi cần được đối chiếu với nội dung PDF.

Quy tắc cập nhật:

- `issued_date` (Ngày văn bản) và `issuing_agency` (Nơi gởi): cập nhật theo PDF nếu đọc ra được một giá trị duy nhất, kể cả khi database đang có giá trị cũ.
- `document_code` (Số/ký hiệu): chỉ điền khi database đang trống. `title` (Trích yếu) cũng chỉ điền khi trống; OCR toàn trang được chấp nhận nếu parser tìm thấy trích yếu có cấu trúc và không có dấu hiệu lỗi rõ. Bỏ qua mục nội dung đánh số trong thân văn bản, nhãn “Nội dung …” không có dấu phân cách, trích yếu có dấu hiệu nhiều chữ bị nhận thành số/ký tự lạ, chuỗi chữ hoa lẫn thường bất thường, hoặc câu bị cắt ở cuối. Các đề xuất bị chặn vẫn hiện trong Excel để đối chiếu. Bộ lọc này giảm lỗi rõ ràng nhưng không thể chứng minh mọi câu tiếng Việt đều đúng.
- `direction` (Phân loại): chỉ điền khi đang `unclassified` hoặc trống, và bộ nhận dạng có dấu hiệu đủ rõ. Quyết định nhận theo tiêu đề hoặc ký hiệu QĐ; văn bản đi theo cơ quan ban hành Agribank Đồng Tháp; văn bản đến chỉ nhận khi thấy cơ quan bên ngoài gửi tới Agribank Đồng Tháp.
- Nếu một bản ghi có nhiều PDF, trường nào đọc ra các giá trị mâu thuẫn thì bỏ qua riêng trường đó. Nếu không đọc được một tệp PDF đính kèm, bỏ qua cả văn bản để tránh chọn nhầm tệp.
- Mỗi văn bản thay đổi được lưu cùng giá trị trước/sau, các trường cập nhật, và danh sách PDF nguồn trong nhật ký văn bản. Nếu dữ liệu bị người khác sửa trong lúc quét, lệnh bỏ qua bản ghi đó.

Chạy xem trước trên 100 bản ghi đầu tiên, không ghi database:

```powershell
php artisan ocr:sync-from-pdf --limit=100 --dry-run
```

Khi xem trước ổn, chạy cùng lệnh nhưng bỏ `--dry-run` để tự cập nhật 100 văn bản. Muốn chạy hết kho, dùng `--limit=0`; có thể chia thành các lô lớn hơn và tiếp tục từ ID cuối đã xử lý:

```powershell
php artisan ocr:sync-from-pdf --limit=0 --dry-run
php artisan ocr:sync-from-pdf --limit=1000 --after-id=0
```

Có thể giới hạn theo chi nhánh hoặc một số ID bằng `--branch=1`, `--id=3 --id=7`. Kết quả OCR được cache theo SHA256 PDF nên lần chạy lại không cần OCR lại các tệp chưa đổi. Lệnh không yêu cầu đánh `OK` theo từng văn bản; `--dry-run` là tùy chọn để xem trước cả lô.

## Cập nhật thủ công qua Excel (tùy chọn)

```powershell
Set-Location D:\hotrobieumau
& 'D:\xampp\php\php.exe' artisan ocr:export-excel --limit=0 --file=ocr-review/ket-qua-kiem-tra-pdf-lan-moi.xlsx
```
1. Trong sheet **Kiem tra PDF**, kiểm tra từng dòng. Gõ chính xác `OK` vào Q **Kiểm tra của bạn** để duyệt số/ký hiệu, ngày, nơi gởi và trích yếu. Nếu muốn cập nhật phân loại, kiểm tra riêng các cột R–T rồi gõ `OK` vào U **Duyệt phân loại**. Nếu một văn bản có nhiều PDF, chỉ duyệt một dòng PDF cho văn bản đó.
2. Chạy lệnh xem trước. Lệnh này không ghi database:

```powershell
php artisan ocr:import-approved-review "ocr-review/ten-file.xlsx"
```

3. Nếu danh sách trường dự kiến bổ sung đúng, chạy lại với `--commit`:

```powershell
php artisan ocr:import-approved-review "ocr-review/ten-file.xlsx" --commit
```

Q và U được xét riêng: Q chỉ duyệt bốn trường metadata; U chỉ duyệt phân loại. Phân loại U có thể đổi giá trị hiện tại nếu mày duyệt đề xuất. Trước khi ghi, lệnh đối chiếu các giá trị hiện tại với ảnh chụp trong workbook và bỏ qua dòng nếu dữ liệu đã đổi từ lúc xuất Excel. Một ID có nhiều dòng PDF được duyệt cùng một loại cập nhật cũng bị bỏ qua để tránh chọn nhầm file. Thay đổi được ghi nhật ký cùng dòng Excel nguồn. Chỉ dùng lệnh `ocr:import-approved-review`; lệnh nháp `ocr:import-excel` không dành cho workbook này.

Hai lệnh cần kết nối được tới database. Trên máy XAMPP, mở XAMPP Control Panel và bật **MySQL** trước khi chạy.

## Kiểm thử

```powershell
php artisan test --compact tests/Unit/DocumentReviewMetadataParserTest.php tests/Feature/DocumentOcrReviewExportTest.php tests/Feature/DocumentOcrPdfSyncCommandTest.php
```

Kiểm thử dùng SQLite trong bộ nhớ và file giả: quy tắc ghi đè ngày/nơi gởi, chỉ điền trường còn trống, giữ phân loại đã có, bỏ trường mâu thuẫn giữa nhiều PDF, chế độ xem trước không ghi database, bỏ ngày dẫn chiếu và giữ an toàn nội dung Excel.
