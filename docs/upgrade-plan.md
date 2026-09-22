# Kế hoạch nâng cấp hiệu suất và trải nghiệm

## Nguyên tắc triển khai

Chia theo đợt có thể kiểm tra và triển khai độc lập. Mỗi đợt ghi kết quả thực tế tại đây, chạy kiểm thử phù hợp và giữ đúng dữ liệu/quyền chi nhánh. Các thao tác migration hoặc cấu hình production có hướng dẫn triển khai riêng; đo cùng dữ liệu trước/sau để đánh giá tốc độ.

## Lộ trình

| Đợt | Phạm vi | Tiêu chí hoàn tất | Trạng thái |
|---|---|---|---|
| 1 | Bản nháp checkbox, chống ghi đè khi lưu/khôi phục; bỏ jQuery/validation/UI nạp trùng; tìm kiếm mobile; bỏ truy vấn không dùng | Kiểm thử hồi quy nháp và API xung đột; suite PHP đạt; asset đúng thứ tự; xác minh tìm kiếm desktop/mobile | Hoàn tất code và kiểm thử local |
| 2 | Tạo Word xử lý tất cả checkbox một lần; rút gọn transaction; đồng bộ timeout/retry queue OCR | Nội dung DOCX giữ nguyên, đo template đại diện trước/sau; job dài không bị lấy lại sớm | Hoàn tất code và kiểm thử local |
| 3 | Import khách hàng/tài khoản theo chunk và batch; index DB chi nhánh; tác vụ nền và tiến độ | Dữ liệu 1k/10k/50k dòng đúng, không mất trường cũ; giảm SQL/RAM; test cách ly chi nhánh và nhập lại | Hoàn tất code và kiểm thử local |
| 4 | Query thông báo, COUNT trùng, payload bảng, lọc ngày, báo cáo; lưu bộ lọc | Đo SQL count/p50/p95, EXPLAIN trên DB staging; kết quả và quyền giữ nguyên | Hoàn tất code, migration và kiểm thử local |
| 5 | Reader sổ giới hạn từ sớm, import/export nền, lịch sử/admin phân trang, scanner và asset/server | Thử tải đồng thời, file lớn và mobile; đo header/cache và khả năng thao tác | Đang triển khai — hoàn tất 5A–5B |

## Đợt 1 — phạm vi kỹ thuật

- Nhóm checkbox lưu danh sách giá trị; radio lưu giá trị; có xử lý nháp cũ và giá trị rỗng.
- Khi server trả nháp muộn, không áp vào form đã được nhập/reset. Lưu từng request một và gộp nội dung mới trong lúc chờ.
- API sử dụng token phiên bản nội dung và transaction để từ chối bản lưu cũ, gồm trường hợp hai tab cùng tạo nháp lần đầu. Mỗi tài khoản chỉ có một nháp dùng chung; đổi mẫu sẽ gộp các trường vừa nhập vào nháp đó. Người dùng nhận thông báo khi có xung đột; nội dung đang nhập được giữ local.
- Bản nháp local ghi trạng thái chưa đồng bộ; lỗi mạng không làm mất nội dung. Không coi việc xếp sendBeacon vào hàng đợi là đã lưu thành công.
- Một bản jQuery và validation trong head; jQuery UI dùng partial `@once` ở trang cần dùng. Giữ thứ tự script để tương thích code inline hiện tại.
- Nối tìm biểu mẫu cho cả desktop/mobile, báo lỗi và dùng text để render tên kết quả.
- Bỏ query recentForms không dùng và query phân trang trước khi bảng tải bằng AJAX.

## Cách triển khai đợt 1

Triển khai controller, JavaScript, Blade và migration cùng phiên bản. Migration gộp các nháp từng mẫu của một tài khoản theo thứ tự cập nhật rồi đặt khóa duy nhất trên `user_id`. Tab đã mở từ phiên bản cũ cần tải lại để sử dụng giao thức lưu nháp có token. Xóa cache view của bản triển khai nếu đang dùng view cache; kiểm tra nháp, in, modal và autocomplete trên môi trường triển khai trước khi thông báo hoàn tất cho người dùng.

## Kết quả kiểm chứng đợt 1

- Toàn bộ suite PHP sau đợt 1: **122 tests / 646 assertions đạt**. Trong đó có 7 bài kiểm thử API nháp và render layout mới. Dùng SQLite `:memory:`, không dùng dữ liệu thật.
- JavaScript: **6 tests đạt**, gồm checkbox/radio, nháp boolean cũ, chờ phiên bản ban đầu, gộp lần lưu, xung đột và reset khi request trước chưa xong.
- Render trang biểu mẫu thật bằng Blade với DB SQLite giả: một jQuery, một validation, một jQuery UI; Bootstrap đứng sau jQuery; có ô tìm mobile và trạng thái nháp.
- Trình duyệt local với fixture dùng asset/module thật và API giả: modal mở/đóng; lưu đúng một checkbox; xung đột không ghi đè server; chọn bản đang nhập lưu thành công; lỗi mạng giữ nội dung và thử lại được; reset rồi tải lại không khôi phục dữ liệu cũ.
- Tìm kiếm desktop và mobile ở viewport **390 × 844** hoạt động, Enter hiện kết quả, chọn kết quả điều hướng được.
- Đã thử nhập ngay sau tải lại trong khi GET nháp bị trì hoãn 1,8 giây: nội dung vừa nhập và checkbox được giữ, sau đó lưu đúng lên server giả lập. Console trình duyệt không ghi nhận lỗi trong các thao tác kiểm tra.
- Đã bổ sung kiểm thử một nháp dùng chung qua nhiều mẫu, chế độ xóa toàn bộ nháp và migration gộp dữ liệu cũ. Migration đã chạy trên MySQL local; chưa đo p50/p95 thực tế.

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

## Kết quả kiểm chứng đợt 2

- Tạo từng mẫu Word một lần, thay toàn bộ placeholder/checkbox trong cùng lượt và chỉ giữ transaction cho phần tăng lượt sử dụng. Bộ tải nhiều mẫu tạo một DOCX, chèn một trang trắng giữa các mẫu.
- Thử trực tiếp bốn mẫu mục tiêu bằng Microsoft Word: file mở được, đủ 14 trang, 7 section, ba trang phân cách trắng; giữ logo, bảng, header/footer và footnote.
- Queue database/Redis/Beanstalkd mặc định giữ job 300 giây; timeout job OCR là 180 giây. Worker dev nghe đúng `document-ocr`; kiểm thử chặn `retry_after` thấp hơn timeout job dài nhất.

## Kết quả kiểm chứng đợt 3

- Import khách hàng/tài khoản dùng chunk 1.000 và batch 500. Mỗi batch tra mã một lần, insert theo lô và upsert bản ghi cũ theo khóa chính; ô trống không ghi đè dữ liệu cũ.
- Upload chuyển sang job `data-imports`, lưu tiến độ ở database chính và tự thiết lập database tenant từ tác vụ. Giao diện hiển thị phần trăm cùng số dòng thêm/cập nhật/bỏ qua.
- Bổ sung index thường cho mã khách hàng, giấy tờ, số tài khoản và liên kết tài khoản-khách hàng. Không áp unique khi chưa xử lý dữ liệu trùng trên DB thật.
- Benchmark SQLite dữ liệu giả: 1.000/10.000/50.000 dòng dùng 4/40/200 câu SQL tenant; lần lượt 0,045/0,402/2,512 giây, bộ nhớ đỉnh 30/40/52 MB. Đây không phải số đo production.
- Kiểm thử gồm file Excel thật, nhập lại, giữ ô trống, dòng trùng trong file, index với dữ liệu trùng, đổi đúng tenant trong worker, quyền xem tiến độ và xóa file tạm. Hướng dẫn triển khai tại [customer-account-import.md](customer-account-import.md).
- Toàn bộ suite PHP sau đợt 2–3: **177 tests / 1.010 assertions đạt**. Toàn bộ JavaScript: **25 tests đạt**. Composer validate, Blade compile, Pint cho các file mới và `git diff --check` đều đạt.

## Kết quả kiểm chứng đợt 4

- Thông báo chuyển sang tải khi mở chuông; render topbar không còn truy vấn văn bản. API giữ đúng phân quyền, loại bản nhập kho/sổ cũ và chỉ trả dữ liệu hiển thị.
- Danh sách tránh lần `COUNT` trùng khi chưa có bộ lọc bổ sung; JSON 30 dòng trên dữ liệu local giảm từ 53.791 xuống 16.984 byte. URL giữ cả bộ lọc, trang, số dòng và thứ tự sắp xếp.
- Lọc ngày so sánh trực tiếp trên cột `DATE`. Báo cáo dùng khoảng năm và conditional aggregation, giảm từ 11 xuống 5 câu SQL.
- Bổ sung bốn composite index cho danh sách văn bản, log, chuyển chi nhánh và thứ tự sổ. `EXPLAIN` local không còn `Using filesort` ở hai truy vấn đại diện.
- Benchmark MySQL local 620 văn bản/3.750 dòng sổ: báo cáo p50 6,727 → 4,934 ms, p95 7,142 → 5,537 ms; đếm danh sách p50 1,942 → 0,936 ms, p95 2,447 → 1,243 ms.
- Toàn bộ suite PHP: **185 tests / 1.085 assertions đạt**. Toàn bộ JavaScript: **27 tests đạt**. Chi tiết và hướng dẫn triển khai tại [phase-4-query-optimization.md](phase-4-query-optimization.md).

## Kết quả kiểm chứng đợt 5A–5B

- Reader web đọc tối đa từng cụm `maxRows + 1` và dừng ngay ở dòng nghiệp vụ thứ 5.001; không còn nạp hết sheet quá lớn rồi mới từ chối. Lệnh CLI không truyền giới hạn vẫn dùng đường đọc một lượt.
- File XLSX giả 6.500 dòng: chế độ web giới hạn mất 1.849,5 ms và đỉnh 52 MB; đường đọc toàn bộ 6.500 dòng mất 2.418,4 ms và đỉnh 64 MB. Đây là số đo local, một lượt cho mỗi chế độ.
- QR giữ hình xem camera Full HD nhưng giới hạn ảnh phân tích còn cạnh dài 960 px. Với khung 16:9, số pixel/RGBA mỗi lượt giảm 75%; lịch quét thích ứng khoảng 5–6 lượt/giây và dừng khi tab bị ẩn.
- Chi tiết văn bản chỉ nạp 10 log và 10 lần luân chuyển đầu tiên. Nút “Xem thêm” tải từng trang 10 dòng bằng `simplePaginate`, không chạy `COUNT(*)`; trường `details` không dùng trên giao diện không còn nằm trong payload.
- Migration `2026_09_18_000005_optimize_document_history.php` bổ sung index `(document_id, created_at, id)` và `(document_id, transferred_at, id)`, đã chạy trên MySQL local ở batch 36.
- Xuất Excel từ danh sách và sổ văn bản chạy trên queue `document-exports`, theo dõi được sau khi đổi trang, tải file private theo đúng tài khoản và tự dọn sau thời hạn. Một lượt worker database thật đã xuất 1.741 dòng thành file 110.031 byte; migration bảng trạng thái đã chạy local ở batch 37.
- Danh sách tài khoản quản trị dùng DataTables server-side, 10 dòng mỗi trang và tìm kiếm/sắp xếp trên database. Kiểm thử 25 tài khoản xác nhận phân trang và lọc đúng.
- Apache `.htaccess` có nén nội dung và cache CSS/JavaScript 7 ngày, ảnh/font 30 ngày. Cấu hình này còn phải xác minh trên staging; Nginx/IIS cần cấu hình tương đương.
- Toàn bộ suite PHP: **194 tests / 1.142 assertions đạt**. Toàn bộ JavaScript: **33 tests đạt**. Chi tiết và hướng dẫn triển khai tại [phase-5-large-data-performance.md](phase-5-large-data-performance.md).

### Phạm vi còn lại

Đợt kế tiếp còn chuyển nhập sổ web sang tác vụ nền nếu cần nhận file vượt giới hạn request, phân trang server-side cho các bảng admin lớn khác và cấu hình cache/nén tương đương nếu production không dùng Apache. Scanner cần thử camera thật trên Android/iOS; lịch sử cần benchmark lại trên staging có nhiều log/luân chuyển. Các migration đợt 3–5 vẫn cần chạy trên staging rồi kiểm tra `SHOW INDEX`, dữ liệu trùng, `EXPLAIN`, header cache và p50/p95 thực tế trước khi production.
