# Đợt 5 — file lớn, xuất nền, lịch sử, QR và bảng quản trị

## Reader sổ Excel

Luồng nhập sổ trên web truyền giới hạn `DOCUMENT_LEDGER_WEB_IMPORT_MAX_ROWS` thẳng vào `DocumentLedgerReader`. Reader đọc tối đa từng cụm `maxRows + 1`, cộng số dòng nghiệp vụ hợp lệ qua mọi sheet và dừng ngay khi vượt giới hạn. Dòng định dạng rỗng không bị tính nhầm là dữ liệu. Nếu cấu hình giới hạn lớn hơn 5.000, kích thước mỗi cụm vẫn không vượt 5.001 dòng.

Lệnh CLI không truyền giới hạn nên vẫn đọc mỗi sheet một lượt. Điều này giữ tốc độ cho tác vụ chủ động chạy trên máy chủ, trong khi request web không thể nạp toàn bộ một sheet 100.000 dòng rồi mới báo lỗi.

Đo local bằng XLSX 212.401 byte gồm 6.500 dòng nghiệp vụ và 6 cột. Mỗi chế độ chạy trong một tiến trình PHP riêng với `memory_limit=512M`; file đo đã được xóa sau khi chạy.

| Chế độ | Kết quả | Thời gian | RAM đỉnh |
|---|---|---:|---:|
| Có giới hạn web 5.000 | Dừng ở dòng hợp lệ 5.001 | 1.849,5 ms | 52 MB |
| Không giới hạn | Trả đủ 6.500 dòng | 2.418,4 ms | 64 MB |

Với file thử này, nhánh web từ chối sớm giảm 23,5% thời gian và 18,8% RAM đỉnh. Đây là một lượt đo local, không đại diện p50/p95 production. Cần thử lại với file thật có nhiều sheet, công thức và dòng trống trên staging.

## Xuất sổ Excel trực tiếp và chạy nền

- Sổ dưới `DOCUMENT_EXPORT_BACKGROUND_MIN_ROWS` (mặc định 5.000 dòng) được tạo và tải trực tiếp. Cách này giúp các lượt xuất thông thường không bị treo nếu worker chưa chạy.
- Sổ từ ngưỡng trên trở lên được gửi sang hàng đợi, nhận URL trạng thái rồi theo dõi tiến độ. Trạng thái được giữ trong `sessionStorage`, nên chuyển trang hoặc tải lại vẫn tiếp tục theo dõi.
- Job chạy trên queue `document-exports`; file hoàn tất nằm trên disk private, chỉ tài khoản đã tạo mới xem trạng thái và tải được. Response tải xuống có `no-store` và `nosniff`.
- Một yêu cầu đang chờ/chạy của cùng tài khoản, chi nhánh và khoảng ngày được dùng lại để tránh tạo job trùng. File hết hạn sau 24 giờ theo cấu hình và lệnh `documents:prune-exports` xóa cả file lẫn bản ghi hết hạn mỗi ngày.
- Nếu queue không nhận được job, yêu cầu được đánh dấu `failed` ngay để lần xuất sau không bị chặn bởi trạng thái `queued` treo.
- Request cũ không gửi `background=1` vẫn tải Excel trực tiếp, giữ tương thích với liên kết hoặc tích hợp hiện có.

Migration `2026_09_18_000006_create_document_exports_table.php` đã chạy trên MySQL local ở batch 37. Một lượt thử thật với database queue đã tạo thành công file 1.741 dòng, 110.031 byte, gồm hai sheet `VB đi sau KT` và `VB QUYET DINH`; job hoàn tất khoảng một giây. File, job và bản ghi thử đã được xóa sau khi xác minh.

## Quét QR trên điện thoại

- Video xem trước vẫn xin Full HD để người dùng căn mã rõ. Canvas dành cho jsQR được co riêng xuống cạnh dài tối đa 960 px.
- Khung Full HD 16:9 giảm từ 2.073.600 xuống 518.400 pixel, tương đương buffer RGBA từ khoảng 8,3 MB xuống 2,1 MB mỗi lượt.
- Cấu hình sáng/tương phản 100% dùng lại buffer vừa chụp, không tạo thêm `Uint8ClampedArray` cho mỗi frame.
- Vòng quét lên lịch lượt kế tiếp sau khi lượt hiện tại hoàn thành, mục tiêu 180 ms và tối thiểu nghỉ 80 ms nếu xử lý nặng. Tab bị ẩn ngừng quét; rời trang dừng camera.
- Asset `scan_camera.js` có query version theo `filemtime`, tránh trình duyệt giữ bản cũ sau triển khai.

Các con số pixel là tính toán từ kích thước canvas. Cần thử trực tiếp camera Android/iOS để đo CPU, nhiệt độ, thời gian nhận mã nhỏ và quyền camera khi chuyển tab.

## Lịch sử chi tiết văn bản

API chi tiết chỉ trả tối đa 10 nhật ký và 10 lần luân chuyển ban đầu, kèm cờ còn dữ liệu. Giao diện hiện nút “Xem thêm” tương ứng và nối từng trang 10 dòng. Endpoint `/api/documents/{id}/history` kiểm tra lại cùng phạm vi quyền xem văn bản, dùng `simplePaginate` nên không đếm toàn bộ lịch sử.

Payload log chỉ chọn `id`, `document_id`, `user_id`, `action`, `created_at` và người thao tác; trường JSON `details` không được giao diện dùng đã bị loại. Payload luân chuyển chỉ chọn các khóa đích, người chuyển, thời điểm, ghi chú và trạng thái.

Migration `2026_09_18_000005_optimize_document_history.php` thêm:

| Bảng | Index | Mục đích |
|---|---|---|
| `document_logs` | `(document_id, created_at, id)` | Lấy log mới nhất và phân trang ổn định |
| `document_transfers` | `(document_id, transferred_at, id)` | Lấy luân chuyển mới nhất và phân trang ổn định |

Migration đã chạy trên MySQL local ở batch 36 và `SHOW INDEX` xác nhận đúng thứ tự ba cột. DB local hiện tối đa một log mỗi văn bản và chưa có bản ghi luân chuyển, nên chưa đủ dữ liệu để công bố p50/p95. Kiểm thử cách ly dùng 13 log và 12 lần luân chuyển xác nhận trang đầu 10 dòng, trang sau đúng phần còn lại và không có truy vấn `COUNT(*)`.

## Danh sách tài khoản quản trị

Trang quản trị tài khoản không còn nạp toàn bộ người dùng khi mở. DataTables gọi endpoint server-side, lấy 10 dòng mỗi trang và thực hiện tìm kiếm, sắp xếp trên query database. Các nút sửa/khóa dùng sự kiện ủy quyền nên tiếp tục hoạt động khi đổi trang; thêm hoặc sửa thành công tải lại đúng trang đang xem. Model người dùng cũng ẩn `password` và `remember_token` khỏi dữ liệu tuần tự hóa.

Kiểm thử cách ly với 25 tài khoản xác nhận trang hai trả 10 dòng và tìm theo email chỉ trả đúng một dòng. Các bảng quản trị lớn khác chưa chuyển sang phân trang server-side trong đợt này.

## Nén và cache asset

`public/.htaccess` bật nén cho CSS, JavaScript, JSON, XML, SVG và văn bản khi Apache có `mod_deflate`. CSS/JavaScript được cache 7 ngày; ảnh và font 30 ngày khi có `mod_expires`/`mod_headers`. Cấu hình không ghi đè header của PHP/API.

Máy local không có Apache CLI nên chưa thể chạy `httpd -t` hoặc đo header thực. Cần xác minh header trên staging; nếu máy chủ dùng Nginx hoặc IIS thì cấu hình tương đương phải đặt ở máy chủ thay vì `.htaccess`.

## Kiểm thử

- PHPUnit: **194 tests / 1.142 assertions đạt**.
- Node test: **33 tests đạt**.
- `composer validate --no-check-publish`, `php artisan view:cache`, Pint và `git diff --check` đạt. Cảnh báo CRLF của Git chỉ là chuẩn hóa line ending khi Git ghi file lần sau.
- Queue database được chạy bằng worker thật cho một lượt xuất, sau đó xác nhận không còn job hoặc bản ghi xuất thử.

## Triển khai

Chạy hai migration main, làm mới cache và khởi động lại worker:

```powershell
php artisan migrate --database=mysql --path=database/migrations/main/2026_09_18_000005_optimize_document_history.php --force
php artisan migrate --database=mysql --path=database/migrations/main/2026_09_18_000006_create_document_exports_table.php --force
php artisan optimize:clear
php artisan queue:restart
php artisan view:cache
```

Worker phải nghe queue mới:

```powershell
php artisan queue:work --queue=default,data-imports,document-exports,document-ocr --tries=2 --timeout=240 --memory=256
```

Scheduler production cần gọi `php artisan schedule:run` mỗi phút để lệnh dọn file xuất chạy lúc 02:30. Sau triển khai, kiểm tra file sổ vượt giới hạn, xuất Excel nền, trang chi tiết trên 10 log/luân chuyển, bảng tài khoản và camera Android/iOS. Việc chuyển nhập sổ web sang job nền, phân trang các bảng admin còn lại và đo header/cache trên máy chủ thật là phần tiếp theo.
