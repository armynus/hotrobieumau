# Nhập kho văn bản cũ bằng sổ Excel và OCR

Luồng xử lý ưu tiên dữ liệu có độ tin cậy cao theo thứ tự:

1. Tên file -> `document_code` (số, ký hiệu văn bản).
2. Folder `NGAY dd-mm-yyyy` -> ngày đến của văn bản đến hoặc ngày chuyển của văn bản đi.
3. Sổ Excel -> các trường nghiệp vụ còn lại.
4. PDF -> chỉ bù `issued_date` và `title` còn thiếu; công việc này chạy bằng queue.

## Kho chung đã nhập trước đây

Các file đã nhập bằng importer cũ đang mang loại `incoming`. Đối chiếu sổ đi trước để sửa đúng những bản thực tế là văn bản đi:

```powershell
php artisan documents:enrich-from-ledger "D:\Agribank văn bản\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx" --direction=outgoing --user=3 --reclassify --dry-run
```

Nếu kết quả hợp lý, bỏ `--dry-run` và thêm `--yes`. Sau đó đối chiếu sổ đến cho phần còn lại.

Chạy thử trước, không thay đổi dữ liệu:

```powershell
php artisan documents:enrich-from-ledger "D:\Agribank văn bản\Văn Thư\Sổ vb đến 2025 (01-01-2026).xlsx" --direction=incoming --user=3 --dry-run
```

Nếu bảng đối chiếu hợp lý thì cập nhật thật:

```powershell
php artisan documents:enrich-from-ledger "D:\Agribank văn bản\Văn Thư\Sổ vb đến 2025 (01-01-2026).xlsx" --direction=incoming --user=3 --queue-ocr-missing --yes
```

Không dùng `--overwrite` trong lần chạy thông thường. Tùy chọn đó chỉ dành cho trường hợp cần lấy sổ Excel ghi đè dữ liệu đã sửa tay.

## Nhập một kho chung mới kèm hai sổ

Importer quét kho đúng một lần. Mỗi file được dò bằng số, ký hiệu trong cả hai sổ; khớp sổ nào thì nhận loại của sổ đó:

```powershell
php artisan documents:import-archive "Z:\KHO VAN BAN CHUNG" --user=3 --direction=auto --incoming-ledger="E:\linh tinh\Văn Thư\Sổ vb đến 2025 (01-01-2026).xlsx" --outgoing-ledger="E:\linh tinh\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx" --unmatched=skip --queue-ocr-missing --dry-run
```

Sau khi kiểm tra bảng thống kê, bỏ `--dry-run` và thêm `--yes` để nhập thật. File không có trong sổ hoặc cùng khớp cả hai sổ sẽ được bỏ qua để tránh phân loại sai. `--date-field=auto` là mặc định đúng: folder là ngày đến với văn bản đến, ngày chuyển với văn bản đi.

## Cấu hình OCR miễn phí, chạy nội bộ

Máy chủ cần Poppler và Tesseract OCR có dữ liệu ngôn ngữ Việt. Nếu các chương trình không nằm trong `PATH`, khai báo đường dẫn tuyệt đối trong `.env`:

```dotenv
DOCUMENT_PDFTOTEXT_BINARY=C:\poppler\Library\bin\pdftotext.exe
DOCUMENT_PDFTOPPM_BINARY=C:\poppler\Library\bin\pdftoppm.exe
DOCUMENT_TESSERACT_BINARY=C:\Program Files\Tesseract-OCR\tesseract.exe
DOCUMENT_TESSERACT_LANGUAGES=vie+eng
```

Kiểm tra trạng thái và số PDF cần xử lý:

```powershell
php artisan documents:queue-metadata-extraction --direction=incoming --branch=1 --dry-run
```

Chạy worker riêng để OCR không chặn web:

```powershell
php artisan queue:work --queue=document-ocr --tries=2 --timeout=180 --memory=256
```

Các lần bổ sung metadata không tạo thông báo “văn bản mới”. Log chỉ lưu engine và tên trường đã cập nhật, không lưu toàn bộ nội dung OCR.
