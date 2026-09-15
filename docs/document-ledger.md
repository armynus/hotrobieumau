# Sổ văn bản độc lập với kho văn bản

## Triển khai

Sao lưu database trước; chạy trong thư mục Laravel trên máy cần cập nhật:

```powershell
php artisan migrate --path=database/migrations/main
php artisan optimize:clear
```

Không chạy migrate:fresh. Migration 2026_09_14 sao chép thông tin hiện tại vào các cột riêng của document_ledger_entries, cho document_id để trống và đổi khóa ngoại thành SET NULL khi xóa văn bản nguồn. Không xóa hoặc sửa văn bản/file cũ. Các bản ghi kho từng được phiên bản cũ tạo khi ghi sổ vẫn được giữ lại; không thể tự kết luận đó là dữ liệu thừa để xóa. Migration không hỗ trợ rollback tự động làm mất thông tin sổ.

Trên server đang phục vụ người dùng: tạm ngừng thao tác ghi/worker import trong lúc cập nhật source và migration, rồi khởi động lại worker để không chạy code cũ. Tải lại trình duyệt bằng Ctrl+F5.

## Hai tính năng biệt lập

| Thao tác | Kho văn bản | Sổ văn bản |
| --- | --- | --- |
| Đăng tải thủ công / import file từ ổ đĩa | Tạo văn bản và file (nếu có) | Không tạo, sửa hoặc cấp số |
| Ghi mới / chỉnh sửa / import Excel sổ | Không tạo, sửa file hay văn bản | Chỉ lưu thông tin sổ |
| Tra sổ khi đăng tải | Chỉ điền input; phải bấm đăng tải mới lưu kho | Chỉ đọc |
| Chủ động Đưa văn bản vào sổ | Chỉ đọc văn bản nguồn | Sao chép thông tin thành dòng sổ riêng |

Không còn checkbox tự ghi sổ khi đăng tải. Không còn tự gắn file vào bản ghi kho dựa trên ID dòng sổ. Sau khi sao chép, sửa thông tin một bên không đồng bộ sang bên kia. Xóa văn bản nguồn không xóa dòng sổ.

## Thao tác cho văn thư

- **Ghi mới vào sổ:** nhập thông tin độc lập, không cần có văn bản trong kho, không gửi thông báo mới.
- **Đưa văn bản vào sổ:** chọn văn bản chưa được đưa vào sổ của chi nhánh mình. Thông tin chỉ được sao chép khi bấm lưu; có thể chỉnh bản sao mà không sửa nguồn. Văn bản từ chi nhánh khác phải được chuyển đến, và chỉ vào sổ đến của mình.
- **Chỉnh sửa** (bút chì): chỉnh chính dòng sổ theo ID, không tìm hoặc sửa bản ghi kho. Chỉ văn thư cùng chi nhánh.
- **Tra sổ khi đăng tải:** chọn năm, nhập số vào sổ hoặc số/ký hiệu ở khung tra sổ. Nếu trùng nhiều dòng thì chọn đúng dòng theo ngày/trích yếu. Các input được điền để tiết kiệm nhập tay, vẫn được chỉnh trước khi đăng tải. Nếu không cần tra sổ thì nhập trực tiếp.
- Sổ đến đầy đủ 10 cột theo mẫu; sổ đi/quyết định có người ký, đơn vị nhận bản lưu và số lượng bản. Ngày văn bản, ngày vào sổ, ký hiệu, trích yếu bắt buộc trên form; tác giả/người ký không bắt buộc.
- Mỗi chi nhánh/năm có ba dãy số riêng: đến, đi, quyết định. Số đến riêng một cột; số đi/quyết định lấy ở đầu số, ký hiệu. Khi ghi sổ, để trống số để cấp tiếp; với đi/quyết định nhập /NHNo.ĐT-TH để tự ghép số. Đăng tải không cấp số sổ.
- Import nhận cả `201-202/ QĐ NHNo.DT-KTNQ` → số sổ `201-202` (giữ một dòng) và `1140 KH-/NHNo-DT-KHDN` → `1140` dù thiếu dấu `/` ngay sau số. Giữ nguyên số, ký hiệu gốc. Dải `201-202` sắp xếp theo 201 nhưng số tự cấp tiếp theo tối thiểu là 203. Không tìm số nằm giữa chuỗi nếu đầu ký hiệu không có số.
- Với các dòng trước đây bị bỏ qua vì hai kiểu số này, chọn lại file Excel và chạy Kiểm tra → Nhập như bình thường (không dùng chế độ chỉ cập nhật). Không cần xóa sổ hoặc sửa file Excel; các dòng khớp đã nhập được đối chiếu theo cơ chế nhập lại hiện có.
- Cho phép số trùng theo sổ gốc. Mỗi dòng có ID riêng; 01 và 1 được tra cùng số nhưng giữ cách viết gốc. Năm sổ theo ngày đến/ngày chuyển, không theo ngày ban hành.
- Xuất Excel chỉ đọc sổ, kể cả dòng không có văn bản/file trong kho. Tháng/quý lọc ngày vào sổ; cả năm lọc năm sổ. Sổ đi tách hai sheet VB đi sau KT và VB QUYET DINH.
- Xóa ô tùy chọn khi sửa thủ công sẽ lưu trống. Import Excel thì ô trống không xóa dữ liệu cũ.

## Nhập Excel

Chọn Nhập Excel → năm, loại sổ, sheet → Kiểm tra trước → đọc thống kê → Nhập các dòng hợp lệ. Đổi file/lựa chọn phải kiểm tra lại. Sổ đến dùng sheet CVĐ; sổ đi dùng VB đi sau KT và VB QUYET DINH.

Import chỉ đối chiếu các dòng sổ trong chi nhánh/năm/loại sổ, **không ghép hoặc cập nhật kho**, dù kho có cùng mã. Mặc định bù trường thiếu; --overwrite ưu tiên ô có nội dung trong Excel. --update-only chỉ cập nhật dòng sổ đã có, không tạo dòng mới.

Dòng thiếu ký hiệu, thiếu/sai số, thiếu/sai ngày vào sổ hoặc thuộc năm khác được bỏ qua/báo lỗi. Không có giới hạn ngầm 2.000 dòng. Thống kê tách lý do và số dòng theo năm. Giữ các dòng trùng; nhập lại ưu tiên dấu vân tay nội dung, vị trí sheet/dòng nguồn và ứng viên duy nhất. Đổi chính số hoặc ký hiệu có thể thành dòng mới; import không đồng bộ xóa toàn bộ sổ.

Giao diện giới hạn 5.000 dòng đọc và 50 MB/file; sổ lớn dùng CLI. Có khóa nhập theo chi nhánh, giao dịch từng dòng; xuất Excel theo lô và có giới hạn số dòng/yêu cầu để giảm tải.

## PowerShell: nhập đủ file và sổ năm 2026

Đặt biến đường dẫn (sửa `$archivePath` nếu kho thực tế ở vị trí khác):

```powershell
$archivePath = 'Z:\KHO VAN BAN CHUNG'
$incomingLedger = 'D:\Agribank văn bản\Văn Thư\Sổ vb đến 2026 (01-01-2026).xlsx'
$outgoingLedger = 'D:\Agribank văn bản\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx'
Test-Path -LiteralPath $archivePath
Test-Path -LiteralPath $incomingLedger
Test-Path -LiteralPath $outgoingLedger
```

### 1. Import file trong kho

Nếu đã import kho rồi, có thể bỏ bước này và chuyển bước 2. Khi muốn nạp thêm file mới, chạy thử:

```powershell
php artisan documents:import-archive "$archivePath" --user=3 --direction=auto --incoming-ledger="$incomingLedger" --incoming-sheet="CVĐ" --outgoing-ledger="$outgoingLedger" --outgoing-sheet="VB đi sau KT" --outgoing-sheet="VB QUYET DINH" --unmatched=unclassified --dry-run
```

Kiểm tra thống kê, rồi chạy thật:

```powershell
php artisan documents:import-archive "$archivePath" --user=3 --direction=auto --incoming-ledger="$incomingLedger" --incoming-sheet="CVĐ" --outgoing-ledger="$outgoingLedger" --outgoing-sheet="VB đi sau KT" --outgoing-sheet="VB QUYET DINH" --unmatched=unclassified --yes
```

File đã nhập báo trùng và không tạo lại; file không khớp sổ vẫn lưu chưa phân loại. File thiếu ngày folder vẫn cần xử lý theo hướng dẫn `document-archive-import.md`; `--fallback-mtime` chỉ dùng khi chấp nhận ngày sửa file làm mốc lưu kho. Chọn các sheet trên không giới hạn năm của file kho; bước sổ tiếp theo mới lọc `--year`.

### 2. Nhập/cập nhật sổ đến

```powershell
php artisan documents:import-ledger "$incomingLedger" --user=3 --direction=incoming --year=2026 --sheet="CVĐ" --dry-run
php artisan documents:import-ledger "$incomingLedger" --user=3 --direction=incoming --year=2026 --sheet="CVĐ" --yes
```

### 3. Nhập/cập nhật sổ đi và quyết định

```powershell
php artisan documents:import-ledger "$outgoingLedger" --user=3 --direction=outgoing --year=2026 --sheet="VB đi sau KT" --sheet="VB QUYET DINH" --dry-run
php artisan documents:import-ledger "$outgoingLedger" --user=3 --direction=outgoing --year=2026 --sheet="VB đi sau KT" --sheet="VB QUYET DINH" --yes
```

Trong mỗi cặp lệnh, đọc kết quả chạy thử trước khi chạy lệnh thật. Lệnh thật có dòng cần kiểm tra sẽ trả mã thoát khác 0, nhưng các dòng hợp lệ đã xử lý vẫn được lưu. Không coi số “bỏ qua” là mất file: nó có thể là dòng năm khác hoặc dòng nguồn cần sửa; xem phần lý do.

Các bước 2–3 chỉ tạo/cập nhật sổ, không thay đổi kho. Bước 1 chỉ nhập file vào kho; Excel dùng làm nguồn đọc metadata, không ghi vào bảng sổ. Có thể chạy hai chức năng độc lập theo thứ tự phù hợp.

### Chỉ cập nhật dòng sổ đã có

Thêm `--update-only` để không tạo dòng sổ mới. Thêm `--overwrite` để cập nhật các trường có nội dung trong Excel. Cả hai tham số đều không tác động kho. Ví dụ:

```powershell
php artisan documents:import-ledger "$incomingLedger" --user=3 --direction=incoming --year=2026 --sheet="CVĐ" --update-only --overwrite --dry-run
php artisan documents:import-ledger "$incomingLedger" --user=3 --direction=incoming --year=2026 --sheet="CVĐ" --update-only --overwrite --yes
```

Không cần OCR để đọc hai file Excel. Lệnh `documents:enrich-from-ledger` cũ chỉ bổ sung metadata, không thay cho chức năng vào sổ mới. Muốn dữ liệu xuất ra sổ đầy đủ, dùng `documents:import-ledger`.

## Gửi riêng

Nếu đã tạo Giám đốc/Phó Giám đốc mà không hiện trong danh sách nhận: vào **Quản lý người dùng → Chỉnh sửa**, chọn đúng **Chi nhánh** và **Chức Vụ Thực Tế**, rồi lưu. Model quản trị cũ từng bỏ qua `position_id`, `department_id`, `document_role` lúc tạo mới; phiên bản này đã sửa. Các tài khoản cũ bị mất chức vụ cần gán lại, không tự suy đoán chức vụ từ tên tài khoản/phòng ban. Danh sách ban giám đốc và kiểm tra người nhận ở API cùng dùng chức vụ cấp 1/2 thuộc chi nhánh đang thao tác.

Quyền xem kho từ ngày 15/09/2026 chỉ có **Riêng Tư / Bình Thường / Công Khai**, luôn giới hạn theo nơi được chọn. Xem [document-visibility.md](document-visibility.md) để triển khai migration, bảo vệ link file và kiểm tra quyền. Sổ vẫn là tính năng độc lập, chỉ dành cho văn thư.
