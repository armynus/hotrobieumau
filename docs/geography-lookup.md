# Tra cứu xã/phường: bản đơn giản

Giao diện `/merger_lookup` giữ hai tab **Trước sáp nhập / Sau sáp nhập**, chọn tỉnh–huyện–xã bằng ô tìm kiếm như trước. Tab sau sáp nhập chọn được tất cả tỉnh, không cố định Đồng Tháp. Một địa bàn cũ có nhiều nơi nhận sẽ hiện đủ các nơi, kèm phạm vi/ghi chú, không lấy kết quả đầu tiên.

## Dữ liệu

Bộ toàn quốc tổng hợp từ ba Excel người dùng cung cấp: 34 tỉnh/thành mới, 3.321 đơn vị mới, 10.602 dòng nguồn. 6 liên kết xác định sai được loại khỏi tra cứu nhưng giữ trong quản trị; còn 10.596 liên kết dùng được. 1.130 ghi chú đối chiếu có thể tải CSV ở quản trị (gồm cả điểm đã sửa và điểm cần kiểm tra, không có nghĩa 1.130 liên kết sai).

Mốc nguồn: **01/07/2025**. Phần Đồng Tháp có 308 quan hệ đã đối chiếu NQ1663; các tỉnh còn lại dùng theo Excel, chưa chứng nhận toàn bộ quan hệ bằng nghị quyết. Tên/mã TMS 8 số, diện tích và mô tả nguồn phụ không tự ghi đè mã hành chính 5 số hoặc quan hệ cũ–mới. Không khẳng định đây là địa chỉ hiện hành đầy đủ năm 2026.

## Cài đặt và nhập

Chỉ thêm hai bảng `geography_imports`, `geography_records` vào database chính. Không sửa/xóa bảy bảng tra cứu cũ hoặc địa chỉ khách hàng.

```powershell
php artisan migrate --path=database/migrations/main/2026_09_25_140000_create_geography_catalog_tables.php
php artisan geography:import --prepared --yes
```

Nếu cấu hình `GEOGRAPHY_DB_CONNECTION` khác mặc định, migrate với `--database=<tên kết nối đó>`. Không migrate vào database chi nhánh.

Trang quản trị **Dữ liệu xã/phường** (`/admin/geography`) chỉ có:

- Nhập bộ toàn quốc có sẵn hoặc file Excel/CSV/JSON khác. Nhập xong dùng ngay, không có bước duyệt/công bố.
- Tìm/lọc danh sách, sửa thông tin địa bàn cũ, phạm vi, ghi chú hoặc loại/hiện lại liên kết.
- Tải ghi chú đối chiếu và bản nguồn hiện tại.

Nhập một file là thay **toàn bộ bộ tra cứu** đang dùng, không cộng dồn. Bản nhập trước và file nguồn vẫn lưu riêng tư để có thể phục hồi. Nhập lại chính file hiện tại không nhân bản hay xóa những sửa tay. Muốn đổi tên/mã mới, chỉnh bộ nguồn rồi nhập lại. Không cập nhật địa chỉ khách hàng tự động.

File nguồn riêng tư: `storage/app/private/geography/imports` (theo disk `local`). File lỗi được từ chối trước khi chuyển sang dữ liệu mới. Dòng Excel thiếu định danh hoặc trùng hệt được bỏ qua và có ghi chú; trùng nhưng mâu thuẫn tên/mã/phạm vi sẽ báo lỗi. Mã mới giữ dạng chuỗi 5 số, gồm cả số 0 đầu.

## Chuẩn bị dữ liệu từ ba Excel

Script hiện có được thêm chế độ **chỉ xuất JSON**, không tạo thêm workbook/giao diện duyệt:

```powershell
& 'C:\Users\Admin\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe' --max-old-space-size=8192 scripts/administrative-data/build-review.mjs --json-only --input-dir 'C:\Users\Admin\Downloads' --output-dir 'outputs\ward-review-2026-09-24'
```

Nạp `normalized-review.json` qua admin hoặc `php artisan geography:import <đường-dẫn-file> --yes`. Script kiểm tra hash cả ba file trước khi dùng cache; đổi cấu trúc nguồn sẽ báo lỗi thay vì âm thầm mất dòng. Dùng Node và `@oai/artifact-tool` bundled như trên; không cần Node để chạy website.

Admin cũng đọc trực tiếp `vietnam-sap-nhap-phuong-xa.xlsx` hoặc bảng có các cột `tinh_cu,huyen_cu,xa_cu,tinh_moi,xa_moi,ma_xa_moi,pham_vi,ghi_chu`. Hai file danh mục chỉ có mô tả không đủ quan hệ để nhập riêng; cần ghép bằng script. Khi nhận đúng bản Excel gốc đã đối chiếu, importer sử dụng các hiệu chỉnh của bộ ba file có sẵn. File khác được coi là nguồn chưa kiểm chứng, không tự gán nhãn đã xác minh.

Giới hạn file 25 MB (PHP/web server có thể thấp hơn); bộ toàn quốc có sẵn không cần upload. Bộ dữ liệu lớn cần PHP memory_limit phù hợp, khuyến nghị 512 MB. CSS/JS nạp trực tiếp có cache-busting; không cần build npm.

## Kiểm thử

```powershell
php vendor/bin/phpunit tests/Feature/GeographyCatalogTest.php
$env:WARD_REVIEW_DIR = 'D:\hotrobieumau\outputs\ward-review-2026-09-24'
& 'C:\Users\Admin\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe' --test tests/js/administrative-data.test.mjs
```

PHP tests dùng SQLite trong bộ nhớ và filesystem giả, không thay database thật.
