# Hỗ trợ biểu mẫu — giao diện và bộ hồ sơ

Triển khai theo thứ tự giao diện → bộ hồ sơ → độ tin cậy và xử lý Word.

Sau khi cập nhật mã nguồn, chạy migration phân nhóm trường:

```powershell
php artisan migrate --path=database/migrations/main/2026_09_17_000001_add_grouping_to_form_fields_table.php --force
```

## Sử dụng

1. Mở **Hỗ trợ biểu mẫu → Tất cả mẫu & bộ hồ sơ** (`/forms`). Các liên kết nhóm cũ vẫn dùng được.
2. Tìm tên/nhóm có dấu hoặc không dấu, lọc nhóm, xem mẫu đã ghim hoặc dùng gần đây. Ghim lưu riêng theo tài khoản và chi nhánh trên trình duyệt hiện tại.
3. **Điền mẫu** mở một mẫu; chọn 2–10 ô bên trái rồi **Tạo bộ hồ sơ** để nhập chung. Lựa chọn được giữ qua bộ lọc và phân trang, trong phiên tab.
4. Màn hình nhập chia nhóm; trường có cùng mã giữa các mẫu chỉ xuất hiện một lần và có nhãn số mẫu dùng chung. Không hiển thị danh sách tên mẫu tại từng trường để giao diện gọn hơn. Các trường khác mã vẫn tách riêng.
5. **Kiểm tra thông tin** liệt kê chỗ thiếu/sai, bấm để chuyển đến ô cần sửa. Ô trống được cảnh báo và có lựa chọn tiếp tục tải như luồng cũ; lỗi định dạng cần sửa trước. Đây là kiểm tra dữ liệu nhập, chưa phải bộ quy tắc thẩm định nghiệp vụ.
6. **Tải Word** tạo DOCX; **Tải bộ Word** tạo một DOCX duy nhất chứa các mẫu theo thứ tự đã chọn, có một trang trống ngăn cách hai mẫu. Nút khóa trong lúc xử lý; lỗi mạng/hết phiên không chuyển khỏi form.
7. Mỗi tài khoản có đúng một bản nháp dùng chung cho mọi mẫu. Khi chuyển sang mẫu khác, các trường trùng tên được điền lại; dữ liệu riêng của mẫu trước vẫn được giữ để dùng lại khi quay lại. Nút “Xóa nháp và bắt đầu lại” thay toàn bộ bản nháp dùng chung bằng trạng thái vừa đặt lại.
8. **Làm mới nháp** hỏi xác nhận rồi xóa cả mã khách hàng ẩn, tài khoản và dữ liệu tra cứu đã nhớ; giữ thông tin giao dịch viên/chi nhánh theo luồng cũ.

## Quản lý nhóm và thứ tự trường

1. Mở **Quản trị → Danh sách trường dữ liệu** (`/admin/form_fields`).
2. Khi thêm hoặc sửa trường, chọn **Nhóm nội dung** và nhập **Thứ tự hiển thị**.
3. Có 5 nhóm cố định: Thông tin khách hàng; Giấy tờ pháp lý & đại diện; Tài khoản, thẻ & dịch vụ; Nội dung giao dịch / yêu cầu; Thông tin lập hồ sơ.
4. Số thứ tự nhỏ hiển thị trước trong cùng nhóm. Nên dùng các mốc 10, 20, 30… để dễ chèn trường mới ở giữa.
5. Migration tự phân nhóm dữ liệu cũ và cấp thứ tự theo từng nhóm. Sau đó lựa chọn của quản trị viên là nguồn dữ liệu chính; mã trường không còn quyết định nhóm khi đã có cấu hình.

## Thay đổi cần lưu ý

- Xuất Word **không còn tự cập nhật dữ liệu khách hàng/tài khoản**. Trên mẫu đơn, dùng nút **Lưu thông tin khách hàng** và xác nhận CIF để lưu riêng. Các trường trống không xóa dữ liệu đã có.
- Lưu khách hàng yêu cầu đúng một CIF cá nhân hoặc tổ chức. Tài khoản đã thuộc CIF khác bị từ chối; transaction trên kết nối tenant hoàn tác cả thay đổi khách hàng khi có lỗi.
- Chỉ lưu các trường được khai báo trong mẫu và mã liên kết ẩn. Việc xuất bộ không ghi hồ sơ khách hàng.
- Dán clipboard và chọn khách hàng thông báo cho bộ lưu nháp, gồm mã ẩn; lựa chọn checkbox vẫn giữ dạng mảng.
- Trước xuất bộ, kiểm tra chữ ký của danh sách trường và nội dung file mẫu. Mẫu thay đổi/xóa sau khi mở trang sẽ yêu cầu tải lại. Không trả bộ Word thiếu mẫu; dọn file tạm khi lỗi. Bộ đếm chỉ cập nhật sau khi tạo đủ bộ.

## Hiệu năng và cấu trúc

- `SupportFormDocumentService` dùng chung cho mẫu đơn/bộ hồ sơ, giữ phép chuyển ngày, số tiền và ký tự của renderer cũ.
- `SupportFormBundleDocumentService` ghép trực tiếp các section OOXML vào một DOCX, đổi ID xung đột và mang theo style, numbering, ảnh, header/footer, footnote cùng thiết lập trang. Mỗi khoảng cách là một section riêng với header/footer rỗng nên trang phân cách không dính nội dung mẫu trước.
- Checkbox được gom thành map và cập nhật qua `SupportFormService::updateCheckboxContentControls`: một lần mở ZIP/parse XML/ghi tài liệu. Giữ định dạng run của mẫu nếu có.
- Trang nhập chỉ lấy định nghĩa các trường của mẫu. Tra cứu khách hàng bỏ dữ liệu tài khoản lặp ở cấp ngoài; vẫn giữ `customer.accounts` cho giao diện.
- Chưa thay đổi index DB thực tế hoặc đưa xuất bộ vào queue; tối đa 10 mẫu/request và throttle 10 lượt/phút. Tối ưu truy vấn tra cứu trên dữ liệu lớn cần đo EXPLAIN trước khi chọn index.
- Nhóm và thứ tự trường được lưu trong `form_fields.content_group` và `form_fields.display_order`. `FormWorkspaceService::groups` chỉ suy luận theo mã cho dữ liệu cũ chưa có cấu hình.
- Chưa triển khai danh mục phiên bản mẫu cho quản trị viên, PDF preview, bộ mẫu gợi ý theo nghiệp vụ hoặc nhiều nháp đặt tên.

## Kiểm chứng

- `php vendor/bin/phpunit --filter "FormBundleTest|FormDraftTest|SupportFormCheckboxTest"`: kiểm tra merge trường, DOCX tổng hợp thực, trang trống phân cách, Unicode, số 0 đầu, version guard, lỗi giữa bộ, phân quyền phiên, lưu khách hàng riêng và rollback tenant, XML checkbox và hồi quy nháp.
- Đã mở bộ thử nghiệm bằng Microsoft Word với bốn mẫu 1G/DVTK-CN, 01 THE, 01a NHĐT và 02a NHĐT: Word nhận 7 section; ba trang phân cách đều trống; logo, bảng, header/footer và footnote hiển thị đầy đủ trên 14 trang kết quả. Do Word chỉ có một bộ thiết lập tài liệu dùng chung, dòng giao dịch viên cuối mẫu 02a có thể dồn sang trang cuối khi ghép; nội dung không bị mất.
- `node --test tests/js/*.test.mjs`: tìm kiếm không dấu, lọc/ghim/gần đây, kiểm tra trường và toàn bộ kiểm thử JavaScript hiện có.
- Đã thử trình duyệt: chọn qua nhiều bộ lọc → tìm khách hàng → điền chung → xuất bộ Word; lưu/khôi phục nháp và checkbox; danh sách lỗi nhảy đến trường. Kiểm tra layout desktop và viewport 390×844, không tràn ngang.
- Toàn bộ suite PHP: **180 tests / 1.030 assertions đạt**. Toàn bộ JavaScript: **25 tests đạt**.

## Fixture trình duyệt độc lập

Chạy `php -S 127.0.0.1:8793 -t public tests/browser/form-workspace-smoke.php`, mở `http://127.0.0.1:8793/forms`.

Router này dùng SQLite riêng tại `storage/framework/testing/form-workspace-smoke.sqlite`, phiên riêng trong `form-workspace-sessions` và mẫu Word giả `_form_workspace_smoke_1.docx` đến `_form_workspace_smoke_6.docx` trong `storage/app/public`. Không nối database nghiệp vụ. Chỉ dùng bằng lệnh development server trên loopback; không cấu hình router kiểm thử này trên máy chủ thật. Dữ liệu fixture được tạo lại khi xóa database fixture và chạy lại.
