# Quản trị cơ cấu tổ chức và nâng cấp giao diện người dùng

## Kết quả rà database local

- `users` có 23 tài khoản. Cả 23 chưa có `user_ipcas`; 16 chưa gán phòng ban và 16 chưa gán chức vụ. Ba trường này được giữ không bắt buộc vì dữ liệu hiện tại chưa đầy đủ.
- `branches` có một chi nhánh loại I và hai chi nhánh loại II. Hai chi nhánh loại II trước đây chưa có `parent_id`; dữ liệu local đã được gán về chi nhánh loại I Đồng Tháp.
- Trước khi bổ sung, chỉ chi nhánh 1 có phòng ban. Sau khi chạy lệnh nhập, số phòng ban theo chi nhánh là 8 / 4 / 4; tất cả có mã và không có tên trùng trong cùng chi nhánh.
- Không có mã chi nhánh trùng. Mọi `users.branch_id` hiện đều tham chiếu chi nhánh hợp lệ.

Migration `2026_09_18_000007_harden_organization_structure.php` đổi `users.branch_id` từ `varchar` sang `BIGINT UNSIGNED`, thêm foreign key `users.branch_id -> branches.id`, index cho `users.branch_id`, `branches.branch_code` và hai index tra cứu phòng ban theo chi nhánh. Migration đã chạy trên MySQL local ở batch 38.

Schema hiện đủ cho các chức năng tổ chức đang có. `branch_tax_date` vẫn là chuỗi do migration cũ tạo; form mới chỉ ghi định dạng ISO `YYYY-MM-DD`, nhưng nên chuyển hẳn sang kiểu `DATE` sau khi kiểm tra dữ liệu production. Hệ thống chưa có nhật ký riêng cho thay đổi tài khoản/chi nhánh của quản trị viên; đây là phần nên bổ sung nếu cần truy vết kiểm toán.

Migration `2026_09_20_000002_create_transaction_offices_table.php` bổ sung thực thể phòng giao dịch trực thuộc chi nhánh và khóa ngoại nullable `users.transaction_office_id`. Khi xóa phòng giao dịch, tài khoản được giữ lại và liên kết PGD tự chuyển thành `NULL`. Migration đã chạy trên MySQL local ở batch 40.

## Màn quản trị

### Tài khoản

- Thêm trường IPCAS không bắt buộc và hiển thị mã ngay dưới tên tài khoản.
- Hiển thị đúng phòng ban, chức vụ thực tế, quyền hệ thống, vai trò văn thư và trạng thái. Nhãn “Chức vụ” cũ từng hiển thị `role_id` đã được sửa thành “Quyền hệ thống”.
- Danh sách phòng ban trong modal được lọc theo chi nhánh. Backend từ chối phòng ban thuộc chi nhánh khác, kể cả khi request được gửi thủ công.
- Có thể gán phòng giao dịch không bắt buộc cho tài khoản. Danh sách PGD tự lọc theo chi nhánh; backend từ chối PGD tạm ngưng hoặc thuộc chi nhánh khác.
- Email và IPCAS được kiểm tra trùng; mật khẩu khi sửa là không bắt buộc; phòng ban và chức vụ tiếp tục không bắt buộc.

### Chi nhánh

- Có thể quản lý `branch_type`, `parent_id`, mã số thuế, ngày/nơi cấp, đơn vị chủ quản, địa chỉ, điện thoại, fax và địa danh.
- Chi nhánh loại II bắt buộc chọn một chi nhánh loại I đang hoạt động. Không thể đổi một chi nhánh loại I sang loại khác khi nó còn quản lý đơn vị loại II.
- Tên và mã chi nhánh được kiểm tra trùng. Khi tạo chi nhánh mới, database tenant và bộ phòng ban theo loại được tạo tự động; nếu khởi tạo database lỗi, bản ghi và database dở dang được dọn lại.

### Phòng giao dịch

- Màn **QLý Chi Nhánh → Phòng giao dịch** cho phép thêm và sửa PGD trực thuộc từng chi nhánh.
- Mỗi PGD có tên, mã, địa chỉ, địa danh, số điện thoại, fax, email, người phụ trách và trạng thái hoạt động.
- Tên và mã PGD không được trùng trong cùng chi nhánh; có thể dùng lại ở chi nhánh khác.
- Tài khoản đã gán vẫn hiển thị PGD tạm ngưng để quản trị viên nhận biết và điều chuyển. PGD tạm ngưng không thể gán mới.

### Phòng ban và chức vụ

- Phòng ban quản lý thêm mã phòng, phòng cấp trên và trạng thái. Tên/mã chỉ được trùng giữa các chi nhánh khác nhau; phòng cấp trên phải thuộc cùng chi nhánh.
- Chức vụ quản lý thêm mã chức vụ và trạng thái; tên/mã được kiểm tra trùng.
- `DepartmentSeeder` không còn `truncate()` dữ liệu thật. Seeder dùng cơ chế đồng bộ có thể chạy lại mà không xóa phòng ban hoặc làm mất liên kết user.

## Nhập phòng ban chi nhánh loại II

Bộ mặc định nằm trong `config/organization.php`:

| Mã | Phòng ban |
|---|---|
| BGD | Ban Giám đốc |
| KHKD | Phòng Kế hoạch và Kinh doanh |
| KTNQ | Phòng Kế toán và Ngân quỹ |
| TH | Phòng Tổng hợp |

Xem trước mọi chi nhánh loại II:

```powershell
php artisan organization:seed-type2-departments --dry-run
```

Nhập cho tất cả hoặc một số chi nhánh:

```powershell
php artisan organization:seed-type2-departments
php artisan organization:seed-type2-departments --branch=2 --branch=3
```

Lệnh tìm theo mã hoặc tên trong từng chi nhánh, bổ sung mã còn thiếu và không tạo bản ghi trùng. Trên local, lượt đầu thêm 8 phòng ban cho chi nhánh 2 và 3; lượt hai giữ nguyên cả 8.

## Giao diện người dùng

- Layout dùng nền sáng, card có phân cấp rõ, thanh trên cùng cố định khi cuộn, sidebar gradient và trạng thái menu trang chủ đúng route.
- Trang chủ có khối giới thiệu chi nhánh, bốn thẻ thống kê có thể mở nhanh và nhóm truy cập nhanh tới biểu mẫu, khách hàng, văn bản, địa giới.
- Bảng biểu mẫu gần đây an toàn hơn khi render tên, có trạng thái rỗng tiếng Việt và bỏ hai thư viện biểu đồ không dùng trên trang chủ.
- Bổ sung liên kết bỏ qua menu cho bàn phím, focus rõ và chế độ giảm chuyển động.
- Trang thông tin cá nhân hiển thị PGD, mã PGD, địa chỉ và thông tin liên hệ của PGD đã gán.
- Kiểm tra trực quan ở viewport 1.440 × 900 và 390 × 844: không có tràn ngang; sidebar ẩn đúng trên mobile; hero, thẻ thống kê và hành động nhanh xếp lại đúng breakpoint.

## Kiểm thử và triển khai

- PHPUnit: **203 tests / 1.202 assertions đạt**.
- Node: **33 tests đạt**.
- Blade cache, Composer validate, Pint và kiểm tra giao diện trình duyệt đều đạt.

Triển khai schema rồi nhập bộ phòng ban:

```powershell
php artisan migrate --database=mysql --path=database/migrations/main/2026_09_18_000007_harden_organization_structure.php --force
php artisan migrate --database=mysql --path=database/migrations/main/2026_09_20_000002_create_transaction_offices_table.php --force
php artisan organization:seed-type2-departments --dry-run
php artisan organization:seed-type2-departments
php artisan optimize:clear
php artisan view:cache
```
