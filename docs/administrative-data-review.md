# Chuẩn hóa dữ liệu tra cứu xã/phường

## Phạm vi đợt này

Chuẩn hóa ba workbook người dùng cung cấp và đối chiếu Đồng Tháp theo mốc **01/07/2025**. Không migration, không ghi database, không thay route/giao diện và không công bố dữ liệu chưa kiểm chứng.

Đầu vào giữ nguyên:

- `vietnam-sap-nhap-phuong-xa.xlsx`: 10.602 dòng quan hệ; làm khung dữ liệu nguồn, không coi tất cả quan hệ là đúng.
- `Danh-muc-Phuong-xa_moi-1.xlsx`: 3.321 dòng mô tả. Chỉ ghép bổ sung có truy vết, không ghi đè loại đơn vị hoặc tỉnh hợp thành.
- `Danh-muc-Phuong-xa_moi_34-tinh-thanh-sau-sat-nhap.xlsx`: 3.321 dòng danh mục có hệ mã 8 chữ số. Giữ hệ mã này riêng với mã hành chính 5 chữ số; chưa chứng nhận mã TMS.

## Căn cứ kiểm chứng

- [NQ1663/NQ-UBTVQH15, Điều 1, khoản 1–102](https://xaydungchinhsach.chinhphu.vn/toan-van-nghi-quyet-so-1663-nq-ubtvqh15-sap-xep-cac-dvhc-cap-xa-cua-tinh-dong-thap-nam-2025-119250616203652359.htm): tên và thành phần đơn vị mới, phạm vi toàn bộ/một phần/phần còn lại.
- [QĐ19/2025/QĐ-TTg, trang Công báo 58–62](https://congbaocdn.chinhphu.vn/CongBaoCP/VanBan/2025/6/45430/57441-1-2025921-92219-2025-qd-ttg.pdf): mã tỉnh Đồng Tháp 82; 102 mã đơn vị mới.
- Biến thể `Mỹ Quý` / `Mỹ Quí` chỉ được ghi nhận riêng trong địa bàn Tháp Mười, theo nghị quyết và [tài liệu Sở NN&PTNT Đồng Tháp năm 2024](https://lichhop.dongthap.gov.vn/sct/thumoihop/747_KH-SNN_29022024-signed_01.pdf). Không dùng quy tắc thay i/y hàng loạt.

Danh sách thành phần kỳ vọng được chép độc lập từ nghị quyết trong `scripts/administrative-data/dong-thap-reference.mjs`; không sinh bằng cách chấp nhận lại liên kết của Excel. Văn bản nguồn và danh mục mã đối chiếu nằm trong thư mục `scripts/administrative-data/sources`.

## Kết quả Đồng Tháp

- 102 đơn vị mới: 82 xã, 20 phường; tên/mã đối chiếu QĐ19.
- 305 định danh cũ trong dữ liệu hai tỉnh; 308 quan hệ được đối chiếu NQ1663.
- 6 liên kết nguồn sai: dòng 10427, 10430, 10442, 10445, 10595, 10597. Phường 4/6 Mỹ Tho, Phường 3/4 Cai Lậy, Phường 3/4 Sa Đéc bị nối thêm vào Cao Lãnh. Giữ nguyên các dòng gốc với trạng thái loại để truy vết.
- Ba đơn vị cũ có hai đích thực sự: Gáo Giồng, Phú Thuận B, Tân Thạnh (Thanh Bình).
- `Phần còn lại` không được đổi thành tỷ lệ hoặc `nhập chủ yếu`. Nhãn gốc vẫn được giữ cạnh phạm vi đã xác minh.

Huyện/tỉnh cũ theo workbook: phân biệt bằng địa bàn ghi trong nghị quyết hoặc tên duy nhất trong dữ liệu hai tỉnh cũ. Chưa xác minh mã hành chính cũ, diện tích, ranh giới ấp/thôn, mã TMS hay thay đổi sau mốc 01/07/2025. Không mô tả bộ này là đầy đủ địa chỉ hiện hành năm 2026.

## Đầu ra

Thư mục mặc định: `outputs/ward-review-2026-09-24/`.

- `doi-chieu-xa-phuong.xlsx`: Tổng quan, Đồng Tháp, Danh mục mới, Liên kết, Cần kiểm tra. Có bộ lọc, cố định tiêu đề, cột ý kiến duyệt riêng. Ý kiến Excel chưa tự áp dụng lên JSON/database.
- `normalized-review.json`: toàn bộ đơn vị, quan hệ, trạng thái, lỗi cần duyệt, dòng nguồn, các giá trị bổ sung nguyên gốc và SHA-256 workbook.
- `dong-thap-reviewed.json`: chỉ quan hệ Đồng Tháp đã đối chiếu, kèm danh sách bị loại và giới hạn kiểm chứng. `productionReady: false` là có chủ ý: chưa tích hợp quy trình duyệt/công bố.
- Các tệp `*-extract.json`, PNG và `verification.json` phục vụ tái lập/kiểm tra; không phải dữ liệu công bố.

## Chạy lại

Sử dụng Node bundled của Codex và gói `@oai/artifact-tool` đi kèm. Không cài thêm thư viện vào dự án Laravel.

```powershell
& 'C:\Users\Admin\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe' --max-old-space-size=8192 scripts/administrative-data/build-review.mjs --input-dir 'C:\Users\Admin\Downloads' --output-dir 'outputs\ward-review-2026-09-24'

$env:WARD_REVIEW_DIR = 'D:\hotrobieumau\outputs\ward-review-2026-09-24'
& 'C:\Users\Admin\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe' --test tests/js/administrative-data.test.mjs
```

Cache chỉ tái sử dụng khi SHA-256 file gốc không đổi. Header/range thay đổi sẽ báo lỗi rõ để kiểm tra cấu trúc, không bỏ qua im lặng. Mã luôn lưu dạng chuỗi. Định danh cũ có tỉnh + huyện + tên có dấu + cấp đơn vị; không trộn Tân Thành với Tân Thạnh. Tìm kiếm không dấu chỉ tạo ứng viên, không tự duyệt ghép tên.

### Kết quả kiểm tra lần tạo này

9 kiểm thử Node đều đạt, gồm đối chiếu lại dữ liệu nguồn thực tế. Kiểm tra trực tiếp gói XLSX đã lưu xác nhận đủ 5 sheet, 4 bảng có bộ lọc và cố định tiêu đề, danh sách chọn ý kiến duyệt, không có ô lỗi công thức, và 994 mã hành chính bắt đầu bằng số 0 vẫn là chuỗi.

Lưu ý môi trường: bộ dựng ảnh của runtime Artifact Tool hiện kết thúc Node với mã 1 dù đã xuất đủ workbook, ảnh và tệp `verification.json`. Hiện tượng cũng tái hiện với một workbook một ô chỉ gọi render, không kèm lỗi hoặc stack trace. Chưa sửa thư viện runtime; không coi mã thoát này là chứng nhận thành công. Các kiểm tra nội dung và cấu trúc file nêu trên được chạy độc lập. Chưa mở kiểm thử tương tác trong Microsoft Excel.

## Đợt triển khai ứng dụng tiếp theo (phương án cũ, đã thay)

Theo yêu cầu ngày 25/09/2026, **không áp dụng quy trình duyệt/công bố bên dưới**. Website dùng giao diện tra cứu hai tab như cũ, nhập toàn quốc dùng ngay, loại lỗi đã biết và tải ghi chú đối chiếu. Xem `docs/geography-lookup.md`. Các kết quả đối chiếu nguồn ở phần trên vẫn giữ giá trị trong phạm vi đã nêu.

1. Nạp JSON vào vùng dữ liệu chờ riêng, không ghi đè 7 bảng tra cứu cũ.
2. Bổ sung đợt nhập, nguồn/căn cứ, trạng thái duyệt theo từng trường và lịch sử quyết định. Chống trùng theo định danh/mã và phiên bản; so sánh trước khi cập nhật.
3. Trang quản trị xem trước, lọc lỗi, duyệt/công bố theo phạm vi; rollback chỉ đợt nhập liên quan.
4. API hai chiều trả đầy đủ các đích, không dùng `first()` cho chiều cũ sang mới. Danh sách xã cũ phải kèm huyện/tỉnh, hỗ trợ đơn vị cũ cấp huyện.
5. UI có tìm không dấu và mã, hai chiều tra cứu, cảnh báo tách một phần, mốc dữ liệu, căn cứ và sao chép. Không tự cập nhật địa chỉ khách hàng.
6. Chỉ các dữ liệu đã duyệt mới hiển thị như kết quả chính thức. Đối chiếu các tỉnh còn lại bằng nghị quyết tương ứng trước khi công bố quan hệ toàn quốc.
