# Kế hoạch nâng cấp hiệu suất và trải nghiệm

## Nguyên tắc triển khai

Chia theo đợt có thể kiểm tra và triển khai độc lập. Mỗi đợt ghi kết quả thực tế tại đây, chạy kiểm thử phù hợp và giữ đúng dữ liệu/quyền chi nhánh. Các thao tác migration hoặc cấu hình production có hướng dẫn triển khai riêng; đo cùng dữ liệu trước/sau để đánh giá tốc độ.

## Lộ trình

| Đợt | Phạm vi | Tiêu chí hoàn tất | Trạng thái |
|---|---|---|---|
| 1 | Bản nháp checkbox, chống ghi đè khi lưu/khôi phục; bỏ jQuery/validation/UI nạp trùng; tìm kiếm mobile; bỏ truy vấn không dùng | Kiểm thử hồi quy nháp và API xung đột; suite PHP đạt; asset đúng thứ tự; xác minh tìm kiếm desktop/mobile | Hoàn tất code và kiểm thử local |
| 2 | Tạo Word xử lý tất cả checkbox một lần; rút gọn transaction; đồng bộ timeout/retry queue OCR | Nội dung DOCX giữ nguyên, đo template đại diện trước/sau; job dài không bị lấy lại sớm | Chưa triển khai |
| 3 | Import khách hàng/tài khoản theo chunk và batch; index DB chi nhánh; tác vụ nền và tiến độ | Dữ liệu 1k/10k/50k dòng đúng, không mất trường cũ; giảm SQL/RAM; test cách ly chi nhánh và nhập lại | Chưa triển khai |
| 4 | Query thông báo, COUNT trùng, payload bảng, lọc ngày, báo cáo; lưu bộ lọc | Đo SQL count/p50/p95, EXPLAIN trên DB staging; kết quả và quyền giữ nguyên | Chưa triển khai |
| 5 | Reader sổ giới hạn từ sớm, import/export nền, lịch sử/admin phân trang, scanner và asset/server | Thử tải đồng thời, file lớn và mobile; đo header/cache và khả năng thao tác | Chưa triển khai |

## Đợt 1 — phạm vi kỹ thuật

- Nhóm checkbox lưu danh sách giá trị; radio lưu giá trị; có xử lý nháp cũ và giá trị rỗng.
- Khi server trả nháp muộn, không áp vào form đã được nhập/reset. Lưu từng request một và gộp nội dung mới trong lúc chờ.
- API sử dụng token phiên bản nội dung và transaction để từ chối bản lưu cũ, gồm trường hợp hai tab cùng tạo nháp lần đầu. Không cần thay schema. Người dùng nhận thông báo khi có xung đột; nội dung đang nhập được giữ local.
- Bản nháp local ghi trạng thái chưa đồng bộ; lỗi mạng không làm mất nội dung. Không coi việc xếp sendBeacon vào hàng đợi là đã lưu thành công.
- Một bản jQuery và validation trong head; jQuery UI dùng partial `@once` ở trang cần dùng. Giữ thứ tự script để tương thích code inline hiện tại.
- Nối tìm biểu mẫu cho cả desktop/mobile, báo lỗi và dùng text để render tên kết quả.
- Bỏ query recentForms không dùng và query phân trang trước khi bảng tải bằng AJAX.

## Cách triển khai đợt 1

Triển khai controller, JavaScript và Blade cùng phiên bản. Tab đã mở từ phiên bản cũ cần tải lại để sử dụng giao thức lưu nháp có token. Xóa cache view của bản triển khai nếu đang dùng view cache; không cần migration. Kiểm tra nháp, in, modal và autocomplete trên môi trường triển khai trước khi thông báo hoàn tất cho người dùng.

## Kết quả kiểm chứng đợt 1

- Toàn bộ suite PHP sau đợt 1: **122 tests / 646 assertions đạt**. Trong đó có 7 bài kiểm thử API nháp và render layout mới. Dùng SQLite `:memory:`, không dùng dữ liệu thật.
- JavaScript: **6 tests đạt**, gồm checkbox/radio, nháp boolean cũ, chờ phiên bản ban đầu, gộp lần lưu, xung đột và reset khi request trước chưa xong.
- Render trang biểu mẫu thật bằng Blade với DB SQLite giả: một jQuery, một validation, một jQuery UI; Bootstrap đứng sau jQuery; có ô tìm mobile và trạng thái nháp.
- Trình duyệt local với fixture dùng asset/module thật và API giả: modal mở/đóng; lưu đúng một checkbox; xung đột không ghi đè server; chọn bản đang nhập lưu thành công; lỗi mạng giữ nội dung và thử lại được; reset rồi tải lại không khôi phục dữ liệu cũ.
- Tìm kiếm desktop và mobile ở viewport **390 × 844** hoạt động, Enter hiện kết quả, chọn kết quả điều hướng được.
- Đã thử nhập ngay sau tải lại trong khi GET nháp bị trì hoãn 1,8 giây: nội dung vừa nhập và checkbox được giữ, sau đó lưu đúng lên server giả lập. Console trình duyệt không ghi nhận lỗi trong các thao tác kiểm tra.
- Kiểm tra cú pháp PHP/JavaScript và `git diff --check` đạt. Chưa triển khai production, chưa thay đổi schema/dữ liệu thật, chưa đo p50/p95 thực tế.

### Chạy lại kiểm thử

PowerShell, từ thư mục project:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
php vendor/bin/phpunit --no-progress
node --test tests/js/support-form-draft.test.mjs
```

Kiểm tra UI bằng dữ liệu giả, ở terminal riêng:

```powershell
php -S 127.0.0.1:8792 -t public tests/browser/upgrade-smoke.php
```

Mở `http://127.0.0.1:8792/upgrade-smoke`. Fixture dùng sessionStorage/localStorage có khóa riêng cho dữ liệu giả, không gọi API nghiệp vụ hoặc DB thật. Dừng terminal sau khi kiểm tra.

### Phạm vi còn lại

Đợt 2 sẽ tập trung tạo Word một lượt và cấu hình thời gian queue OCR. Đợt 3 mới thay đổi index/import và cần kiểm tra schema/dữ liệu trên DB đích. Chuyển asset sang Vite, tối ưu trang lỗi độc lập và cấu hình cache/nén server thuộc đợt 5.
