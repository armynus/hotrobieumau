# Rà soát hiệu suất, tốc độ và trải nghiệm người dùng

Ngày: 15/09/2026. Project: `D:\hotrobieumau`.

## Kết luận

Ưu tiên: sửa lưu nháp, bỏ thư viện JavaScript nạp trùng, tối ưu import khách hàng/tài khoản và quy trình tạo Word. Tiếp theo là truy vấn thông báo, bảng văn bản và báo cáo. Các điểm này có bằng chứng trực tiếp trong code; chưa có số đo để khẳng định website sẽ nhanh hơn bao nhiêu phần trăm.

## Phạm vi và kiểm chứng

- Kiểm kê 263 file PHP/Blade/JS/CSS, khoảng 28.216 dòng trong app, routes, config, database, resources, public/js/user, public/css/user và tests. Đây là số liệu kiểm kê, không phải tuyên bố kiểm chứng từng dòng.
- Rà soát cấu trúc, tìm các mẫu truy vấn/tải tài nguyên trong các tầng; đọc chi tiết luồng văn bản, sổ văn bản, khách hàng/tài khoản, nhập/xuất, tạo biểu mẫu, bản nháp, tìm kiếm, camera và layout dùng chung. Kiểm tra cách nạp một số thư viện trong public/vendor; không audit toàn bộ mã nguồn bên thứ ba.
- Đánh giá working tree hiện tại, gồm các thay đổi chưa commit có sẵn. Chỉ bổ sung báo cáo này, không sửa mã ứng dụng.
- Chạy `php vendor/bin/phpunit --no-progress`, đặt DB của tiến trình kiểm thử thành SQLite `:memory:`: **115 tests, 613 assertions, đều đạt**, thời gian 4,874 giây, bộ nhớ báo bởi PHPUnit 78 MB. Đây là số liệu bộ kiểm thử, không phải benchmark website.
- Chạy hàm `readValues()` thực tế bằng Node với hai checkbox cùng tên: đã tái hiện lỗi mất lựa chọn.
- Đọc một số giá trị cấu hình local: queue/database, cache/database, session/database; `retry_after=90`; chưa cache config/route. Không suy ra cấu hình production từ kết quả local.
- Chưa đo Lighthouse, thời gian phản hồi production, kế hoạch thực thi SQL, số bản ghi/index thực tế, mức sử dụng CPU hoặc tải đồng thời. Các nhận định về index dựa trên migration trong repo.

## Các mục nên triển khai

### 1. P1 — Bản nháp làm mất lựa chọn checkbox

**Vị trí:** [support-form-draft.js:27](D:/hotrobieumau/public/js/user/support-form-draft.js:27), [component checkbox:26](D:/hotrobieumau/resources/views/components/check-value-to-check-box.blade.php:26).

Các checkbox của một nhóm dùng chung `name`. `readValues()` lại gán `payload[name]` bằng một boolean cho từng ô: ô sau ghi đè ô trước. Khi khôi phục, cùng một boolean được áp vào cả nhóm.

Đã tái hiện: chọn `MB_APLUS`, không chọn `MB_SMS` → dữ liệu hiện tại `{MobileBanking: false}`. Dữ liệu cần giữ là danh sách giá trị được chọn, ví dụ `{MobileBanking: ['MB_APLUS']}`.

**Cách sửa:** checkbox nhóm lưu mảng giá trị, radio lưu giá trị được chọn; khôi phục bằng cách so sánh `value` của từng input. Có xử lý tương thích bản nháp cũ. Kiểm chứng bằng nhóm không chọn, chọn một, chọn nhiều và tải lại trang.

### 2. P1 — Bản nháp server về chậm có thể đè nội dung mới

**Vị trí:** [support-form-draft.js:93](D:/hotrobieumau/public/js/user/support-form-draft.js:93), [FormDraftController.php:27](D:/hotrobieumau/app/Http/Controllers/User/FormDraftController.php:27).

Trang gửi GET bản nháp rồi lập tức khôi phục local. Khi GET hoàn tất, `writeValues(parsed)` tiếp tục ghi dữ liệu server lên form mà không kiểm tra người dùng đã nhập hay chưa. Local có `saved_at` nhưng không được dùng để so phiên bản; API GET chỉ trả payload. Các POST autosave cũng chưa có cơ chế chống dữ liệu cũ đến sau ghi đè dữ liệu mới.

**Cách sửa:** dùng revision do server quản lý, theo dõi form đã thay đổi, chỉ khôi phục khi còn phù hợp. Giữ tối đa một lần lưu đang chạy và gộp bản cập nhật tiếp theo. Không phát autosave hàng loạt khi đang khôi phục. Hiển thị “Đang lưu / Đã lưu / Lưu thất bại” để người dùng biết tình trạng thật.

**Kiểm chứng:** làm chậm GET 2–3 giây rồi nhập ngay; sửa nhanh nhiều lần; mở hai tab; mất mạng rồi kết nối lại. Không được mất nội dung mới.

### 3. P1 — Nạp jQuery nhiều lần và thay instance sau Bootstrap

**Vị trí:** [head_template.blade.php:11](D:/hotrobieumau/resources/views/head_template.blade.php:11), [transaction_form.blade.php:284](D:/hotrobieumau/resources/views/user/page/transaction_form.blade.php:284), [view_data_customer.blade.php:81](D:/hotrobieumau/resources/views/user/page/view_data_customer.blade.php:81).

Head nạp jQuery 3.2.1, validation, rồi jQuery 3.6.0 và validation khác. Nhiều trang nạp thêm `vendor/jquery/jquery.min.js` ngay sau Bootstrap. Vì vậy có trang thực thi jQuery ba lần; việc thay instance sau khi đăng ký plugin còn có thể làm các lời gọi như `$.fn.modal` không còn trên instance hiện tại. Trang biểu mẫu cũng nạp jQuery UI riêng trong khi `search_topbar` nạp lại.

Ba tài nguyên dư xác định được có tổng **197.814 byte, khoảng 193 KiB trước nén**, trên các trang có cả ba bản jQuery. Đây là dung lượng file, không phải số byte mạng tiết kiệm trên mọi lần tải vì còn nén và cache.

**Cách sửa:** một jQuery duy nhất trong layout; thứ tự jQuery → Bootstrap/plugin → script trang. Nạp DataTables, Flatpickr, Chart, scanner theo trang sử dụng. Hợp nhất dần hai hệ SweetAlert đang có. Có thể dùng Vite hiện có để tạo asset có hash; hiện view không dùng `@vite`. Chuyển inline script phù hợp trước khi áp dụng `defer`, tránh phá thứ tự phụ thuộc.

**Kiểm chứng:** đăng nhập, mở/đóng modal, sidebar mobile, validation, autocomplete, DataTables, in và upload. Network chỉ còn một tài nguyên jQuery cho mỗi trang.

### 4. P1 — Import tài khoản chưa bật đọc/chèn theo lô; cả hai import truy vấn từng dòng

**Vị trí:** [AccountInfoImport.php:11](D:/hotrobieumau/app/Imports/AccountInfoImport.php:11), [CustomerInfoImport.php:19](D:/hotrobieumau/app/Imports/CustomerInfoImport.php:19), [AccountController.php:69](D:/hotrobieumau/app/Http/Controllers/User/AccountController.php:69), [CustomerController.php:93](D:/hotrobieumau/app/Http/Controllers/User/CustomerController.php:93).

`AccountInfoImport` có `chunkSize()` và `batchSize()` nhưng chỉ implements `ToModel, WithHeadingRow`; hai phương thức kích thước chưa kích hoạt cơ chế tương ứng. Cả hai importer đều SELECT theo mã cho từng dòng; dòng cũ còn UPDATE riêng. Với 10.000 dòng cập nhật hợp lệ, riêng phần tra cứu và cập nhật có thể tạo khoảng 20.000 câu SQL, chưa tính chi phí khác.

Hai controller chạy `Excel::import()` ngay trong HTTP request và nâng `max_execution_time` lên 9.000 giây. Điều này không làm import nhanh hơn hoặc bảo đảm proxy/trình duyệt chờ được.

**Cách sửa:** đọc chunk, lấy các mã đã tồn tại bằng một truy vấn mỗi chunk, gộp dữ liệu rồi ghi theo lô. Có thể dùng batch upsert sau khi có khóa duy nhất phù hợp và giữ đúng quy tắc không ghi đè trường cũ bằng dữ liệu trống. Với file lớn, lưu file tạm và xử lý bằng job; UI trả mã tác vụ và tiến độ. Job phải mang `branch_id` và thiết lập/reset kết nối tenant rõ ràng vì không có session HTTP.

Đã đối chiếu cách bật concern và yêu cầu khóa của upsert với [Laravel Excel — batch inserts](https://docs.laravel-excel.com/3.1/imports/batch-inserts.html) và [chunk reading](https://docs.laravel-excel.com/3.1/imports/chunk-reading.html).

### 5. P1 khi dữ liệu lớn — Thiếu index cho mã khách hàng/tài khoản trong migration

**Vị trí:** [migration customer_info:16](D:/hotrobieumau/database/migrations/branch/2025_01_06_070725_create_customer_info_table.php:16), [migration account_info:16](D:/hotrobieumau/database/migrations/branch/2025_01_20_072137_create_accountinfo_table.php:16), [tìm khách hàng:172](D:/hotrobieumau/app/Http/Controllers/User/UserSupportFormController.php:172).

Trong các migration chi nhánh chưa thấy index cho `customer_info.custno`, `customer_info.identity_no`, `account_info.idxacno`, `account_info.custseq`. Chúng được dùng để tìm bản ghi, import và nối khách hàng với tài khoản.

**Cách sửa:** kiểm tra `SHOW INDEX` và dữ liệu trùng trên DB thực tế; thêm index cho các cột tra cứu/join. Chỉ thêm UNIQUE cho `custno`/`idxacno` sau khi xác nhận nghiệp vụ và xử lý dữ liệu trùng. Áp dụng cho từng DB chi nhánh.

Tìm kiếm hiện dùng `%từ khóa%` trên cả mã và tên. Index B-tree thông thường không biến tìm kiếm chứa chuỗi thành một lần tra cứu nhanh. Nên ưu tiên khớp chính xác/tiền tố cho mã, giữ tìm theo tên phù hợp nhu cầu; dùng EXPLAIN để xác nhận trước/sau. Xem [MySQL — range optimization](https://dev.mysql.com/doc/refman/8.0/en/range-optimization.html).

### 6. P1 — Tạo Word mở và ghi lại DOCX cho từng checkbox

**Vị trí:** [SupportFormService.php:26](D:/hotrobieumau/app/Services/SupportFormService.php:26), [UserSupportFormController.php:490](D:/hotrobieumau/app/Http/Controllers/User/UserSupportFormController.php:490).

Mỗi `updateCheckboxContentControl()` mở ZIP, đọc `word/document.xml`, parse DOM, tìm node rồi đóng ZIP. Controller gọi hàm này cho từng checkbox, gồm các vòng lặp nghề nghiệp/chức vụ/thẻ/dịch vụ. Một biểu mẫu đầy đủ có thể lặp hàng chục lượt mở/parse/ghi cùng tài liệu.

**Cách sửa:** dựng map `tag => checked`, mở DOCX một lần, parse XML một lần, cập nhật tất cả tag, ghi một lần. Đo thời gian trên cùng template và kiểm tra file đầu ra về checkbox, ký tự tiếng Việt và bố cục.

`print()` còn mở transaction từ đầu tới sau khi tạo Word. Nên rút gọn phần transaction DB, xử lý file tách khỏi thời gian giữ transaction, dọn file tạm khi lỗi. Cần lưu ý models khách hàng/tài khoản dùng kết nối tenant còn `DB::beginTransaction()` dùng default; không giả định transaction hiện tại bao phủ cả hai DB.

### 7. P2 — Thông báo làm tăng truy vấn trên mọi trang người dùng

**Vị trí:** [AppServiceProvider.php:25](D:/hotrobieumau/app/Providers/AppServiceProvider.php:25), [DocumentQueryService.php:16](D:/hotrobieumau/app/Services/DocumentQueryService.php:16).

Mỗi lần render topbar: tìm User, đếm văn bản chưa đọc, lấy 5 văn bản. Query dùng điều kiện quyền và các EXISTS trên permissions/transfers/reads/logs; còn eager-load `documentType`, `creator` dù topbar không dùng. Tác động xuất hiện cả khi mở biểu mẫu hoặc tra khách hàng.

**Cách sửa:** tái sử dụng User đã load trong phạm vi request; bỏ relation/cột không dùng cho topbar. Cân nhắc tải nội dung dropdown khi mở. Nếu cache, cache theo user + chi nhánh + phiên bản quyền, TTL ngắn và vô hiệu hóa khi đọc/chuyển/thu hồi/phân phối; không dùng cache chung cho danh sách có phân quyền.

**Đo:** số SQL và thời gian DB của một trang biểu mẫu trước/sau; truy vấn permission không được tăng theo số dòng hiển thị.

### 8. P2 — Danh sách văn bản đếm trùng và trả dư dữ liệu

**Vị trí:** [DocumentApiController.php:68](D:/hotrobieumau/app/Http/Controllers/User/DocumentApiController.php:68), [DocumentQueryService.php:16](D:/hotrobieumau/app/Services/DocumentQueryService.php:16), [document-list.js:252](D:/hotrobieumau/public/js/user/document-list.js:252).

API luôn tính `recordsTotal` và `recordsFiltered`. Khi không có bộ lọc bổ sung, hai phép đếm có cùng phạm vi. Query còn tải model văn bản và creator/documentType, rồi serialize cả model, trong khi bảng hiện chỉ có loại, mã, trích yếu, ngày, thao tác và file.

**Cách sửa:** tái sử dụng tổng khi điều kiện thực sự giống nhau; định nghĩa rõ dữ liệu trả cho bảng và chọn cột/relation tối thiểu, giữ trường cần tính capabilities. Có thể lấy metadata chỉnh sửa khi người dùng mở modal. Giữ giới hạn 100 dòng hiện tại.

Bộ lọc, tab loại và trang hiện chưa được lưu vào URL/state. Trở về từ chi tiết phải thao tác lại. Nên lưu trạng thái lọc, sort, pagination; bảo đảm ô tìm của DataTables và ô keyword riêng có quy tắc rõ ràng.

### 9. P2 — Lọc ngày và index sổ cần khớp với truy vấn thực tế

**Vị trí:** [DocumentQueryService.php:90](D:/hotrobieumau/app/Services/DocumentQueryService.php:90), [DocumentController.php:138](D:/hotrobieumau/app/Http/Controllers/User/DocumentController.php:138), [DocumentLedgerTableService.php:27](D:/hotrobieumau/app/Services/DocumentLedgerTableService.php:27), [DocumentLedgerService.php:17](D:/hotrobieumau/app/Services/DocumentLedgerService.php:17).

Các cột ngày đang là DATE nhưng dùng `whereDate`; báo cáo dùng `whereYear`. Hàm bọc cột có thể hạn chế cách dùng index tùy engine/phiên bản. Sổ thường lọc branch/year/book rồi sort sequence_number/number_key/id, trong khi migration hiện có index theo ngày và mã, chưa thấy index theo toàn bộ thứ tự này. `nextNumber()` còn tính MAX(sequence_number) theo cùng phạm vi.

**Cách sửa:** dùng so sánh ngày trực tiếp hoặc khoảng đầu năm–đầu năm sau; giữ nguyên quy tắc ngày NULL và ngày thay thế. Thử index `(branch_id, year, book, sequence_number, number_key, id)` bằng EXPLAIN và số đo, rồi cân nhắc chi phí ghi/dung lượng. Không thêm hàng loạt index mà chưa đo.

### 10. P2 — Trang báo cáo chạy nhiều phép đếm độc lập

**Vị trí:** [DocumentController.php:99](D:/hotrobieumau/app/Http/Controllers/User/DocumentController.php:99).

Controller tính thống kê tháng, danh sách năm, nhiều tổng theo hướng văn bản, ngày thiếu và trạng thái đã đọc bằng các truy vấn riêng; topbar tiếp tục chạy truy vấn thông báo.

**Cách sửa:** gộp các tổng cùng phạm vi bằng conditional aggregation; tái sử dụng kết quả phù hợp. Cache thống kê tổng hợp theo năm/phạm vi với TTL rõ ràng nếu chấp nhận trễ. Các chỉ số toàn hệ thống và chỉ số người dùng được xem phải giữ đúng ý nghĩa nghiệp vụ, không gộp nhầm phạm vi.

### 11. P2 — Giới hạn import sổ được kiểm tra sau khi đọc toàn bộ dòng

**Vị trí:** [DocumentLedgerController.php:153](D:/hotrobieumau/app/Http/Controllers/User/DocumentLedgerController.php:153), [DocumentLedgerReader.php:145](D:/hotrobieumau/app/Services/DocumentLedgerReader.php:145).

Reader đã giới hạn sheet/cột, nhưng vẫn nạp vùng dữ liệu của từng sheet và gom tất cả dòng vào mảng. Controller chỉ kiểm tra trần 5.000 dòng sau khi `read()` xong. File vượt trần vẫn tiêu tốn RAM/CPU trước khi bị từ chối. Preview và xác nhận cũng upload/parse lại file.

**Cách sửa:** truyền giới hạn xuống reader, đọc từng khoảng dòng và dừng sớm; bảo vệ cả sheet có vùng sử dụng rất lớn. Có thể lưu file đã upload hoặc kết quả parse bằng token có thời hạn, gắn user/branch/fingerprint. Xác nhận vẫn cần kiểm tra trạng thái DB hiện tại và giữ quy tắc ghép dòng/trùng số của sổ. File lớn xử lý job hoặc CLI.

Export hiện đã dùng `FromQuery`, chunk, giới hạn dòng và lock. Với workbook lớn còn chậm, cân nhắc job và trạng thái tải về; chunk truy vấn không đồng nghĩa toàn bộ workbook có bộ nhớ cố định.

### 12. P2 — Thời gian lấy lại job OCR ngắn hơn thời gian xử lý cho phép

**Vị trí:** [ExtractDocumentMetadata.php:25](D:/hotrobieumau/app/Jobs/ExtractDocumentMetadata.php:25), [queue.php:42](D:/hotrobieumau/config/queue.php:42), [hướng dẫn worker:193](D:/hotrobieumau/docs/document-archive-import.md:193).

Đã xác nhận cấu hình local dùng database queue với `retry_after=90`; job đặt timeout 180 giây. Nếu job còn chạy sau 90 giây và có worker khác, job có thể được lấy lại trong khi lượt đầu chưa kết thúc. `ShouldBeUnique` giúp chống dispatch trùng, không thay thế cấu hình thời gian reservation phù hợp.

**Cách sửa:** đặt `retry_after` lớn hơn timeout với khoảng dự phòng, ví dụ 240–300 giây nếu giữ timeout 180; kiểm tra riêng timeout tiến trình OCR/PDF và khả năng dừng worker trên môi trường triển khai. Khởi động lại worker sau đổi cấu hình. Dùng đúng queue `document-ocr`, vì lệnh queue mặc định trong composer dev không chỉ định queue này.

Quan hệ giữa timeout và retry_after được mô tả trong [Laravel 11 — queues](https://laravel.com/docs/11.x/queues#job-expiration-and-timeouts). Cần kiểm tra production có override cấu hình hay chưa.

### 13. P2 — Tìm biểu mẫu trên mobile chưa được nối sự kiện

**Vị trí:** [topbar.blade.php:28](D:/hotrobieumau/resources/views/user/layouts/topbar.blade.php:28), [search_topbar.blade.php:63](D:/hotrobieumau/resources/views/search_topbar.blade.php:63).

Autocomplete chỉ gắn vào `#search_topbar` ở vùng desktop bị ẩn trên màn hình nhỏ. Ô tìm trong dropdown mobile không có selector tương ứng và nút `type=button` không có handler tìm kiếm.

**Cách sửa:** dùng component/selector chung cho hai ô, placeholder tiếng Việt, chọn kết quả hoặc Enter dẫn đúng trang. Kiểm tra keyboard, focus, màn hình 360–430 px. Đổi `lang="en-GB"` thành `vi` ở layout tiếng Việt và dùng button thật cho thao tác như In/Làm mới thay vì span.

### 14. P2 trên thiết bị yếu — Scanner xử lý toàn khung hình mỗi 50 ms

**Vị trí:** [scan_camera.js:244](D:/hotrobieumau/public/js/user/scan_camera.js:244), [scan_camera.js:500](D:/hotrobieumau/public/js/user/scan_camera.js:500).

Canvas có thể theo độ phân giải video. Mỗi 50 ms, script đọc ảnh, duyệt pixel điều chỉnh sáng/tương phản và gọi jsQR ngay trên main thread. Chưa thấy xử lý visibilitychange để tạm dừng khi tab ẩn.

**Cách sửa:** giới hạn độ phân giải dùng nhận diện, ưu tiên vùng QR, thử lịch quét 100–200 ms hoặc thích ứng theo thời gian xử lý. Tách nhận diện sang Worker khi cần; tạm dừng khi tab ẩn và đóng camera khi rời trang. Đo thời gian nhận diện và độ mượt trên điện thoại thật, không chỉ giảm tần suất rồi giả định tốt hơn.

## Việc nhỏ và mục theo dõi tiếp

| Mục | Bằng chứng và hướng xử lý |
|---|---|
| Bỏ truy vấn không dùng | [UserController.php:26](D:/hotrobieumau/app/Http/Controllers/User/UserController.php:26) lấy recentForms nhưng không truyền view; AJAX lấy lại. Hai trang khách hàng/tài khoản gọi paginate(10) dù bảng dữ liệu lấy AJAX. Bỏ truy vấn thừa sau kiểm tra view không dùng biến data. |
| Lấy đúng trường biểu mẫu | [UserSupportFormController.php:44](D:/hotrobieumau/app/Http/Controllers/User/UserSupportFormController.php:44) lấy FormField::all rồi lọc trong PHP; dùng whereIn(field_code, fields của form) và select cột cần thiết. |
| Autocomplete khách hàng | [UserSupportFormController.php:177](D:/hotrobieumau/app/Http/Controllers/User/UserSupportFormController.php:177) serialize customer kèm accounts, lại trả accounts ở cấp ngoài; bỏ dữ liệu lặp. Nếu tải chi tiết khi chọn, đo thêm độ trễ thao tác này. |
| Loading/upload/export | Upload file tới 50 MB chỉ hiện spinner; thêm phần trăm upload và tách trạng thái “server đang xử lý”. [document-list.js:477](D:/hotrobieumau/public/js/user/document-list.js:477) mở lại nút export sau 5 giây dù chưa biết hoàn tất. [support-form-draft.js:190](D:/hotrobieumau/public/js/user/support-form-draft.js:190) đặt disabled trên span In và mở lại sau 200 ms; cần button thật và trạng thái in riêng. |
| API hết session | [TenantDatabase.php:16](D:/hotrobieumau/app/Http/Middleware/TenantDatabase.php:16) redirect login cho mọi request; với AJAX nên trả 401 JSON để client báo hết phiên rõ ràng thay vì lỗi parse hoặc lỗi bảng chung chung. |
| Lịch sử văn bản tăng dần | [DocumentApiController.php:236](D:/hotrobieumau/app/Http/Controllers/User/DocumentApiController.php:236) tải toàn bộ logs/transfers. Khi có nhiều lịch sử, lấy phần gần nhất và phân trang/lazy-load phần còn lại. |
| Trang admin | Danh sách users/forms/fields lấy toàn bộ rồi render DataTables ở client. Chuyển bảng lớn sang server-side; danh mục nhỏ có thể giữ hiện tại. |
| CLI kho lớn | [DocumentLedgerEnrichmentService.php:42](D:/hotrobieumau/app/Services/DocumentLedgerEnrichmentService.php:42) lấy toàn bộ văn bản chi nhánh kèm relations bằng get(); chuyển chunkById/lazyById và giữ thống kê lũy kế. |
| Asset và triển khai | public/.htaccess trong repo chưa có quy tắc nén/cache asset; máy chủ có thể đã cấu hình ở nơi khác. Kiểm tra header thật, OPcache, config/route/view cache, asset có hash, kích thước icon. Chỉ cache dài tài nguyên tĩnh phù hợp; giữ kiểm tra quyền và chính sách cache của file văn bản. |

## Những điểm đã làm tốt nên giữ

- Danh sách văn bản và sổ đã phân trang/sắp xếp ở DB, chặn tối đa 100 dòng/request.
- Danh sách văn bản dùng withExists để kiểm tra transfer cho văn thư loại II, tránh truy vấn lại transfer từng dòng.
- Lookup sổ khi đăng tải đã có debounce 450 ms, hủy request cũ và kiểm tra generation.
- Import sổ có preview/fingerprint/token, lock theo chi nhánh, thống kê lỗi và giới hạn kết quả lỗi.
- Export sổ có FromQuery, chunk, lock, giới hạn dòng; file văn bản đi qua endpoint kiểm tra quyền.
- Reader sổ chỉ nạp sheet/cột được chọn; matcher lập chỉ mục trong bộ nhớ theo mã; tái tổ chức kho đã dùng chunkById.

## Thứ tự thực hiện và tiêu chí nghiệm thu

1. **Độ tin cậy và tải trang:** sửa hai lỗi bản nháp, hợp nhất jQuery/plugin, nối tìm kiếm mobile, bỏ truy vấn không dùng. Thêm kiểm thử hồi quy cho bản nháp và thử các modal thật trên trình duyệt.
2. **Các thao tác nặng:** batch/chunk import, index DB chi nhánh, xử lý checkbox Word một lượt, sửa thời gian queue. Dùng dữ liệu giả 1.000/10.000/50.000 dòng và template Word đại diện; kiểm tra cả kết quả nghiệp vụ.
3. **Tra cứu hằng ngày:** thông báo, COUNT trùng, payload, truy vấn ngày, thống kê báo cáo. Đo SQL count, thời gian DB, kích thước JSON, p50/p95 trên cùng bộ dữ liệu và cùng quyền truy cập.
4. **Tải lớn và mobile:** giới hạn reader sớm, export/import nền, scanner, phân trang lịch sử/admin, cấu hình asset/server theo kết quả đo.

Nên ghi baseline cho đăng nhập, trang chủ, danh sách văn bản, sổ, mở biểu mẫu, in, import và export. Đo riêng lần tải đầu/lần tải lại; phản hồi server và thời gian trình duyệt có thể thao tác; thử 1, 10, 30 người dùng trên môi trường staging. Mục tiêu là cải thiện số đo và giữ đúng quyền/dữ liệu, không dùng số phần trăm ước lượng làm kết luận.

