# Nhập dữ liệu khách hàng và tài khoản

## Luồng xử lý

- File XLS, XLSX, XLSM hoặc CSV tối đa 50 MB được lưu tạm trên disk `local`, không nằm trong thư mục public.
- Request upload chỉ tạo tác vụ rồi trả lại trang. Worker xử lý queue `data-imports`; trang hiển thị số dòng đã xử lý, thêm mới, cập nhật và bỏ qua.
- Mỗi chunk đọc tối đa 1.000 dòng. Dữ liệu được chia thành batch 500 dòng; mỗi batch lấy toàn bộ mã hiện có bằng một query rồi insert/upsert theo lô.
- Job mang tên database chi nhánh tại thời điểm upload và thiết lập lại kết nối `tenant` trước khi đọc hoặc ghi. Trạng thái tác vụ nằm ở database chính và chỉ người tạo trong đúng chi nhánh đọc được.
- Ô trống trong file không xóa giá trị đang có. Dòng thiếu `custno` hoặc `idxacno` được bỏ qua và cộng vào thống kê. Ngày hỗ trợ `YYYYMMDD`, `YYYY-MM-DD` và ô ngày chuẩn của Excel.
- File tạm được xóa khi job hoàn tất hoặc thất bại sau lần thử cuối.

Các index mới là index thường trên `customer_info.custno`, `customer_info.identity_no`, `account_info.idxacno` và `account_info.custseq`. Migration không yêu cầu dữ liệu hiện tại phải duy nhất và không tự xóa bản ghi trùng.

## Triển khai

Chạy migration database chính trước để có bảng theo dõi tác vụ:

```powershell
php artisan migrate --path=database/migrations/main --database=mysql --force
```

Sau đó thêm index cho toàn bộ database chi nhánh:

```powershell
php artisan tenants:migrate-branches
```

Đặt thời gian giữ job lớn hơn timeout dài nhất của job. Cấu hình mặc định hiện tại là 300 giây và job import tối đa 240 giây:

```dotenv
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=300
```

Nạp lại cấu hình và khởi động lại worker:

```powershell
php artisan optimize:clear
php artisan queue:restart
php artisan queue:work --queue=default,data-imports,document-ocr --tries=2 --timeout=240 --memory=256
```

Nếu web và worker chạy trên hai máy khác nhau, disk `local` chứa `storage/app/private/data-imports` phải là vùng lưu trữ dùng chung; nếu không worker sẽ không thấy file upload.

## Kiểm chứng local

- File Excel thật được đọc qua heading row và chunk reader; nhập lại giữ dữ liệu cũ ở các ô trống.
- Test job xác nhận đổi từ một cấu hình tenant sai sang đúng database đã lưu trong tác vụ, cập nhật tiến độ và xóa file sau khi hoàn tất.
- Dữ liệu giả trên SQLite `:memory:` với chunk 1.000 và batch 500 cho kết quả:

| Số dòng | Số câu SQL tenant | Thời gian | Bộ nhớ đỉnh |
|---:|---:|---:|---:|
| 1.000 | 4 | 0,045 giây | 30 MB |
| 10.000 | 40 | 0,402 giây | 40 MB |
| 50.000 | 200 | 2,512 giây | 52 MB |

Số đo này dùng dữ liệu giả và không gồm thời gian đọc workbook từ ổ đĩa. Cần đo lại trên MySQL staging với file và phần cứng thực tế trước khi dùng làm số liệu vận hành.
