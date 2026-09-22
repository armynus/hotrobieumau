# Đợt 4 — tối ưu truy vấn, danh sách và báo cáo

## Thay đổi nghiệp vụ và giao diện

- Chuông thông báo không còn truy vấn văn bản khi render mọi trang. Danh sách và số chưa đọc chỉ tải một lần khi người dùng mở hoặc focus vào chuông; lỗi mạng có thể bấm lại. Dữ liệu từ API được chèn bằng `textContent` để nội dung số, ký hiệu không thể trở thành HTML.
- Danh sách văn bản giữ bộ lọc, trang hiện tại, số dòng/trang và thứ tự sắp xếp trong URL qua các tham số `page`, `length`, `sort`, `dir`. Quay lại từ trang chi tiết hoặc chia sẻ URL sẽ trở về đúng trạng thái.
- API danh sách chỉ trả các trường đang hiển thị, file đính kèm tối thiểu và quyền thao tác. Thông tin đầy đủ vẫn được lấy khi người dùng mở chi tiết/chỉnh sửa.
- Khoảng ngày dùng so sánh trực tiếp trên cột `DATE`. Báo cáo năm dùng khoảng từ đầu năm được chọn đến trước đầu năm kế tiếp; quy tắc ngày thay thế và ngày `NULL` không thay đổi.

## Thay đổi truy vấn

- `DocumentQueryService` không tự eager-load `documentType` và `creator`; endpoint chi tiết chủ động nạp hai quan hệ này khi cần.
- API danh sách tái sử dụng `recordsTotal` làm `recordsFiltered` khi chỉ có phạm vi loại văn bản và chưa có bộ lọc bổ sung. Khi có từ khóa/ngày/trạng thái đọc, API vẫn đếm riêng để giữ đúng DataTables.
- `DocumentReportService` gộp năm nhóm tổng hệ thống vào một conditional aggregation và gộp tổng đã đọc/chưa đọc vào một truy vấn. Phần báo cáo giảm từ 11 xuống 5 câu SQL, không đổi ý nghĩa tổng toàn hệ thống và tổng người dùng được phép xem.
- `DocumentNotificationService` chỉ chọn `id`, `document_code`, `issued_date`, `created_at`; vẫn loại dữ liệu nhập kho/sổ cũ khỏi khu vực văn bản mới và giữ nguyên điều kiện phân quyền.

## Index được bổ sung

Migration `2026_09_18_000004_optimize_document_queries.php` thêm:

| Bảng | Index | Mục đích |
|---|---|---|
| `documents` | `(direction, issued_date, id)` | Lọc loại/ngày và sắp xếp danh sách ổn định |
| `document_logs` | `(document_id, action)` | Kiểm tra văn bản nhập kho và trạng thái xuất bản |
| `document_transfers` | `(document_id, to_branch_id, status)` | Kiểm tra chuyển văn bản đang hiệu lực tới chi nhánh |
| `document_ledger_entries` | `(branch_id, year, book, sequence_number, number_key, id)` | Đọc sổ theo phạm vi và đúng thứ tự hiển thị/cấp số |

Migration đã chạy trên MySQL local ở batch 35. Trước index, hai truy vấn danh sách kiểm tra đều có `Using filesort`. Sau index, MySQL chọn `ledger_scope_sequence_idx` và `documents_direction_issued_idx`; `Extra` còn `Using where`, không còn filesort.

## Kết quả đo local

Dữ liệu đo: 620 văn bản, 3.750 dòng sổ. Mỗi phép đo thời gian chạy 60 lượt sau một lượt làm nóng. Đây là số đo máy local, chưa thay thế benchmark staging/production.

| Luồng | Trước | Sau | Thay đổi |
|---|---:|---:|---:|
| Báo cáo — số SQL | 11 | 5 | giảm 54,5% |
| Báo cáo — p50 | 6,727 ms | 4,934 ms | giảm 26,7% |
| Báo cáo — p95 | 7,142 ms | 5,537 ms | giảm 22,5% |
| Đếm danh sách chưa lọc — số SQL | 2 | 1 | giảm 50% |
| Đếm danh sách — p50 | 1,942 ms | 0,936 ms | giảm 51,8% |
| Đếm danh sách — p95 | 2,447 ms | 1,243 ms | giảm 49,2% |
| JSON 30 dòng danh sách | 53.791 byte | 16.984 byte | giảm 68,4% |
| Truy vấn văn bản khi chỉ render topbar | 2 truy vấn nặng, cộng truy vấn user/relation | 0 | chỉ chạy khi mở chuông |

## Kiểm thử

- PHP: **185 tests / 1.085 assertions đạt**.
- JavaScript: **27 tests đạt**.
- Có kiểm thử riêng cho số lần đếm, payload tối thiểu, phân quyền thông báo, loại dữ liệu archive, topbar không truy vấn sớm, báo cáo 5 câu SQL, tổng nghiệp vụ, khoảng ngày và bốn index mới.
- `php artisan view:cache`, kiểm tra cú pháp PHP và `git diff --check` đạt.

## Triển khai

Chạy migration main trước khi đưa code mới nhận lưu lượng:

```powershell
php artisan migrate --database=mysql --path=database/migrations/main/2026_09_18_000004_optimize_document_queries.php --force
php artisan view:cache
```

Sau triển khai cần đo lại `EXPLAIN`, p50/p95 và kích thước JSON trên dữ liệu staging/production vì độ chọn lọc theo chi nhánh, quyền và năm có thể khác dữ liệu local.
