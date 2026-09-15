# Quyền xem văn bản theo người nhận (15/09/2026)

## Quy tắc

- Văn thư cùng chi nhánh đăng tải luôn xem được.
- Riêng Tư: thêm ban giám đốc được chọn đích danh trong cùng chi nhánh. Không gửi phòng ban hoặc chi nhánh cấp dưới; ẩn checkbox và từ chối yêu cầu API giả mạo.
- Bình Thường: ban giám đốc được chọn; lãnh đạo của phòng ban được chọn. Không mặc định mở cho lãnh đạo không được chọn.
- Công Khai: thêm toàn bộ nhân viên của phòng ban được chọn. Không mở toàn chi nhánh/toàn hệ thống.
- Chọn chi nhánh loại II chỉ cấp quyền cho văn thư chi nhánh nhận. Họ có thể chọn tiếp ban giám đốc/phòng ban thuộc chi nhánh mình. Lãnh đạo/nhân viên cấp dưới không tự có quyền chỉ vì chọn chi nhánh.
- Chức vụ không có hoặc cấp bậc không hợp lệ không được coi là lãnh đạo. Nhân viên/chuyên viên không được đọc mức Bình Thường theo phòng ban.
- Form chỉnh sửa trong chi tiết và sửa nhanh ở danh sách có phần Phân phối và phạm vi xem, điền sẵn các nơi nhận hiện tại. Bỏ tích rồi lưu để thu hồi quyền, không phải thao tác chuyển tiếp cộng thêm.
- Khi lưu form mới ở mức Riêng Tư, bỏ phòng ban và chi nhánh nhận trước đó; chuyển lại Bình Thường/Công Khai phải chọn lại nơi nhận. Lịch sử chuyển tiếp giữ với trạng thái "Đã thu hồi" và ghi nhật ký thay đổi.
- Bỏ CN II thu hồi cả quyền của văn thư, ban giám đốc và phòng ban nhận qua CN đó. Chọn lại CN II không tự phục hồi các nơi đã bị thu hồi; văn thư nhận chọn lại. Giữ nguyên CN II thì không thay đổi phân phối nội bộ của CN đó.
- API chỉnh sửa gửi sync_recipients=1 để đồng bộ danh sách to_user_ids, to_department_ids, to_branch_ids; mảng không gửi được hiểu là bỏ hết nhóm đó. Client cũ không có cờ này chỉ sửa thông tin, không đồng bộ lựa chọn. Metadata và quyền lưu trong cùng transaction; lỗi sẽ rollback cả hai.
- Danh sách, chi tiết API, báo cáo, thông báo và mở file cùng dùng DocumentQueryService. Sổ văn bản không thay đổi.

## Triển khai trên XAMPP/Apache

Sao lưu DB, tạm ngừng thao tác ghi và worker import trong lúc triển khai code + migration. Từ thư mục Laravel:

```powershell
php artisan migrate --path=database/migrations/main
php artisan view:clear
php artisan queue:restart
```

Migration chuyển mức cũ: private (Bình thường cũ) → normal; restricted (Gửi riêng cũ) → private; branch/system → public. Không tự tạo nơi nhận mới, không xóa văn bản/file/sổ. Những văn bản cũ chưa chọn nơi nhận sẽ chỉ còn văn thư quản lý xem được. Không tự rollback vì không được khôi phục quyền xem rộng ngoài ý muốn.

Import kho chỉ nhận --visibility=private|normal|public, mặc định private. CLI không tự đánh dấu chi nhánh/phòng ban nhận.

## Bảo vệ file

Link mới là /documents/attachments/{id}, kiểm tra phiên đăng nhập và quyền xem trước mỗi lần tải. PDF có thể mở trực tiếp, loại file khác tải về. Phản hồi đặt Cache-Control: private, no-store; không cho chạy HTML/SVG trên origin ứng dụng.

public/.htaccess từ chối URL /storage/documents/... kể cả link cũ. Apache phải bật mod_rewrite và AllowOverride cho thư mục public; DocumentRoot phải trỏ vào public của dự án. Không tạo alias khác trực tiếp đến storage/app/public/documents.

Apache dùng .htaccess. Máy phát triển có router server.php chặn cùng đường dẫn; nếu đang chạy php artisan serve thì phải dừng và chạy lại để nhận router mới. Không dùng máy chủ phát triển này để phục vụ production. Nếu dùng Nginx hoặc proxy khác phải chặn tương đương mọi đường dẫn tĩnh vào kho. File người dùng đã tải/cache trước đây không thể thu hồi từ thiết bị họ.

Sau triển khai thử bằng tài khoản nhân viên không được chọn:
1. Danh sách/chi tiết API không thấy văn bản.
2. Link mới trả 404 khi không có quyền.
3. Link cũ /storage/documents/... trả 403 (không trả nội dung file).
4. Đăng xuất: link mới yêu cầu đăng nhập hoặc trả 401.
5. Ctrl+F5 các trang đăng tải, danh sách và chi tiết để bỏ JS cũ.
