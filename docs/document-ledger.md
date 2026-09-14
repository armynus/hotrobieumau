# Quản lý sổ văn bản và nhập kho hiện tại

## Cập nhật source trên máy chủ

Chạy PowerShell trong thư mục dự án Laravel trên máy chủ (nơi có file `artisan`). Đường dẫn kho/Excel phải tồn tại trên máy đó. Các lệnh dưới dùng ID văn thư `3`; thay nếu tài khoản thực tế khác.

Sao lưu database trước khi triển khai, sau đó chạy:

```powershell
php artisan migrate --path=database/migrations/main
php artisan optimize:clear
```

Không dùng `migrate:fresh`. Migration tạo hai bảng riêng: `document_ledger_entries` và `document_ledger_sequences`; không tự đánh số lại dữ liệu đã có. Sau khi cập nhật, tải lại trang bằng Ctrl+F5.

## Quy tắc sổ

- Văn thư mở **Tổng hợp văn bản → Sổ văn bản**. Sổ thuộc chi nhánh của tài khoản.
- Mỗi chi nhánh/năm có ba dãy số độc lập: đến, đi thông thường và quyết định. Năm sổ lấy theo ngày đến/ngày chuyển, không phải ngày văn bản. Ví dụ văn bản ngày 31/12/2025 nhận 05/01/2026 thuộc sổ đến 2026.
- Số đến là cột riêng. Số đi là phần đứng trước dấu `/` của số, ký hiệu: `01 /NHNo.ĐT-QLRR` có số đi `01`.
- Cho phép nhiều dòng cùng số đến/số đi/số quyết định, kể cả cùng số, ký hiệu văn bản. Mỗi dòng có ID riêng, không tự gộp hoặc loại bỏ vì trùng số. `01` và `1` được tra như cùng số nhưng giữ cách viết gốc. Số tự cấp vẫn tăng dưới khóa transaction; số đã dùng không được cấp lại chỉ vì xóa văn bản.
- Có ba trang đăng tải riêng: **Đăng văn bản đến**, **Đăng văn bản đi**, **Đăng quyết định**. Khi tạo mới, chọn **Ghi vào sổ văn bản của chi nhánh**. Đến: để trống số đến để tự cấp. Đi/quyết định: giữ số có sẵn hoặc chọn tự cấp số và nhập `/NHNo.ĐT-TH`. Quyết định có phân loại và dãy số riêng, không dùng chung dãy số đi.
- Có thể đăng thông tin không đính kèm file. Ngày văn bản vẫn bắt buộc trên form đăng mới. Import kho cũ có thể giữ thông tin chưa đầy đủ như nguồn Excel.
- Văn bản chưa vào sổ vẫn có trong danh sách tra cứu nhưng không nằm trong file xuất sổ. Dùng **Đưa văn bản vào sổ** để bổ sung; nút bút chì chỉnh số, năm, ngày vào sổ, ký hiệu chính thức.
- Sổ xuất chỉ lấy dòng của chi nhánh hiện tại. Xuất tháng/quý lọc ngày vào sổ; xuất năm lấy năm sổ. Sổ đi luôn có `VB đi sau KT` và `VB QUYET DINH`, kể cả sheet không có dòng.

## Nhập dữ liệu từ Excel trên giao diện

### Nhập sổ trước, đăng file sau

1. Nhập Excel vào sổ hoặc đăng thông tin không có file như bình thường.
2. Mở đúng trang đăng tải đến/đi/quyết định, chọn **Năm sổ**, nhập số vào sổ hoặc đầy đủ số, ký hiệu tại khung **Lấy thông tin từ sổ**. Nhập số đến/số, ký hiệu ở form cũng tự kích hoạt tra cứu.
3. Nếu chỉ có một dòng khớp, hệ thống tự điền thông tin. Nếu có nhiều dòng trùng số, chọn đúng dòng theo trích yếu, ngày và dòng Excel; chưa chọn thì không được đăng tải.
4. Kiểm tra thông tin, bổ sung các ô bắt buộc còn thiếu (đặc biệt ngày văn bản), chọn file và nơi nhận rồi đăng tải. File được gắn vào **ID văn bản đã có**, không tạo thêm văn bản/dòng sổ. Số và ký hiệu đã chọn được khóa để tránh gắn nhầm.
5. Thành công: form được làm sạch để đăng tiếp. Muốn chọn văn bản khác trước khi lưu, bấm **Bỏ chọn và nhập văn bản khác**.

Tra cứu chỉ trong chi nhánh và loại sổ đã chọn, theo năm vào sổ (không theo năm của ngày ban hành). Chỉ văn thư CN I có quyền đăng tải hiện tại được dùng. Văn bản đã có file của người khác không được lấy để sửa/gắn file; văn bản chỉ có thông tin trong sổ có thể được văn thư cùng chi nhánh đăng file. Nếu dòng sổ vừa bị người khác cập nhật, cần tra lại trước khi lưu.

Nhập Excel vẫn không thông báo văn bản mới. Khi đăng file thủ công vào dòng sổ, văn bản được tính vào luồng thông báo/chưa đọc theo quyền xem và trạng thái đọc hiện có.

### Kiểm tra và nhập Excel

Chọn **Nhập Excel**, năm cần nhập, loại sổ và tên sheet (mỗi tên một dòng), bấm **Kiểm tra trước**, đọc thống kê và lỗi theo sheet/dòng rồi bấm **Nhập các dòng hợp lệ**. Nếu đổi file hoặc lựa chọn phải kiểm tra lại.

File hiện tại có sheet phụ và dữ liệu nhiều năm. Với năm 2026 dùng:

- Sổ đến: `CVĐ`.
- Sổ đi: `VB đi sau KT` và `VB QUYET DINH`. Khoảng trắng cuối tên sheet được xử lý tự động.

Dòng thuộc năm khác bị bỏ qua. Dòng thiếu/sai ngày đến hoặc ngày chuyển chưa xác định được năm sổ nên được báo lại để sửa nguồn; hệ thống không tự lấy ngày văn bản thế vào. Thiếu số hoặc số sai định dạng được báo rõ. Các dòng trùng số/ký hiệu vẫn được giữ riêng; không tùy ý gộp file khi có nhiều văn bản khớp.

**Số, ký hiệu văn bản bắt buộc khi nhập Excel.** Ô trống, chỉ có khoảng trắng hoặc dấu phân cách bị bỏ qua dù đã có số đến, ngày đến hoặc trích yếu. Quy tắc áp dụng cho cả chạy thử, nhập thật, cập nhật bằng ghi đè, giao diện và CLI. Không lấy số đến/trích yếu làm mã thay thế. Dòng có số đến, ngày đến và số, ký hiệu nhưng chưa có file/trích yếu vẫn có thể vào sổ. Không tự xóa các bản ghi thiếu mã đã nhập từ trước.

### Vì sao số dòng nhập ít hơn số dòng trong file?

Kết quả kiểm tra hiển thị tổng số dòng đọc được và số dòng theo năm của **ngày đến/ngày chuyển**. Mục bỏ qua tách riêng dòng khác năm, dòng thiếu số/ký hiệu trong năm cần nhập và các lý do khác. Tên file có “2026” không có nghĩa toàn bộ dữ liệu trong file thuộc năm 2026. Không có giới hạn tự cắt ở dòng 2.000.

Ví dụ file sổ đến được đối chiếu ngày 10/09/2026: sheet `CVĐ` có 2.751 dòng dữ liệu, gồm 2.005 dòng ngày đến năm 2026 và 746 dòng ngày đến năm 2025. Trong nhóm 2026 có 14 dòng thiếu số, ký hiệu nên còn 1.991 dòng trước khi kiểm tra trùng/xung đột; nhóm 2025 có 26 dòng thiếu mã. Các dòng trống/định dạng không được tính là văn bản.

Ở phiên bản trước, 26 dòng bị báo cần kiểm tra vì cùng ký hiệu xuất hiện với nhiều số đến. Phiên bản cho phép số trùng không còn loại các dòng chỉ vì lý do này. Chạy kiểm tra lại để biết thống kê hiện tại; không cần sửa số trong Excel để né trùng.

Nếu cần nhập thêm dữ liệu 2025, kiểm tra ngày trong Excel rồi chạy riêng năm 2025 (hoặc chọn 2025 trên giao diện). Không tự đổi ngày 2025 thành 2026 chỉ để tăng số lượng nhập. Chạy thử trước:

```powershell
php artisan documents:import-ledger "D:\Agribank văn bản\Văn Thư\Sổ vb đến 2026 (01-01-2026).xlsx" --user=3 --direction=incoming --year=2025 --sheet="CVĐ" --dry-run
```

Chỉ sau khi xác nhận đúng sổ/năm mới thay `--dry-run` bằng `--yes`.

Import lặp lại đối chiếu chi nhánh, năm, loại sổ, số và ký hiệu; với số trùng ưu tiên dấu vân tay nội dung, rồi sheet/dòng nguồn, rồi ứng viên duy nhất. Mỗi dòng chỉ được ghép một lần trong một lượt nhập, nên nhập lại cùng file không nhân thêm các dòng trùng đã lưu. Nếu chưa có dòng sổ, đối chiếu mã đã chuẩn hóa và ngày/năm trong kho. Mặc định chỉ bổ sung trường trống; chọn **Cập nhật cả thông tin đã có** để ưu tiên các ô có nội dung của Excel. Ô trống Excel không xóa thông tin cũ. Nếu đổi chính số hoặc ký hiệu dùng đối chiếu, có thể được nhận là dòng mới; không coi import là đồng bộ xóa/thay toàn bộ dữ liệu.

Giao diện giới hạn 5.000 dòng được đọc và 50 MB/file. Sổ lớn dùng CLI dưới đây. Có khóa nhập theo chi nhánh và giới hạn số yêu cầu; mỗi dòng có transaction riêng, nên có thể chạy lại sau khi xử lý lỗi mà không nhập trùng các dòng đã hoàn thành.

## PowerShell: nhập đủ file và sổ năm 2026

Đặt biến đường dẫn (sửa `$archivePath` nếu kho thực tế ở vị trí khác):

```powershell
$archivePath = 'Z:\KHO VAN BAN CHUNG'
$incomingLedger = 'D:\Agribank văn bản\Văn Thư\Sổ vb đến 2026 (01-01-2026).xlsx'
$outgoingLedger = 'D:\Agribank văn bản\Văn Thư\Sổ vb đi 2026 ( 01-01-2026).xlsx'
Test-Path -LiteralPath $archivePath
Test-Path -LiteralPath $incomingLedger
Test-Path -LiteralPath $outgoingLedger
```

### 1. Import file trong kho

Nếu đã import kho rồi, có thể bỏ bước này và chuyển bước 2. Khi muốn nạp thêm file mới, chạy thử:

```powershell
php artisan documents:import-archive "$archivePath" --user=3 --direction=auto --incoming-ledger="$incomingLedger" --incoming-sheet="CVĐ" --outgoing-ledger="$outgoingLedger" --outgoing-sheet="VB đi sau KT" --outgoing-sheet="VB QUYET DINH" --unmatched=unclassified --dry-run
```

Kiểm tra thống kê, rồi chạy thật:

```powershell
php artisan documents:import-archive "$archivePath" --user=3 --direction=auto --incoming-ledger="$incomingLedger" --incoming-sheet="CVĐ" --outgoing-ledger="$outgoingLedger" --outgoing-sheet="VB đi sau KT" --outgoing-sheet="VB QUYET DINH" --unmatched=unclassified --yes
```

File đã nhập báo trùng và không tạo lại; file không khớp sổ vẫn lưu chưa phân loại. File thiếu ngày folder vẫn cần xử lý theo hướng dẫn `document-archive-import.md`; `--fallback-mtime` chỉ dùng khi chấp nhận ngày sửa file làm mốc lưu kho. Chọn các sheet trên không giới hạn năm của file kho; bước sổ tiếp theo mới lọc `--year`.

### 2. Nhập/cập nhật sổ đến

```powershell
php artisan documents:import-ledger "$incomingLedger" --user=3 --direction=incoming --year=2026 --sheet="CVĐ" --dry-run
php artisan documents:import-ledger "$incomingLedger" --user=3 --direction=incoming --year=2026 --sheet="CVĐ" --yes
```

### 3. Nhập/cập nhật sổ đi và quyết định

```powershell
php artisan documents:import-ledger "$outgoingLedger" --user=3 --direction=outgoing --year=2026 --sheet="VB đi sau KT" --sheet="VB QUYET DINH" --dry-run
php artisan documents:import-ledger "$outgoingLedger" --user=3 --direction=outgoing --year=2026 --sheet="VB đi sau KT" --sheet="VB QUYET DINH" --yes
```

Trong mỗi cặp lệnh, đọc kết quả chạy thử trước khi chạy lệnh thật. Lệnh thật có dòng cần kiểm tra sẽ trả mã thoát khác 0, nhưng các dòng hợp lệ đã xử lý vẫn được lưu. Không coi số “bỏ qua” là mất file: nó có thể là dòng năm khác hoặc dòng nguồn cần sửa; xem phần lý do.

Các bước 2–3 vừa ghép/cập nhật văn bản đã có trong kho, vừa tạo thông tin cho văn bản chưa có file. Nếu Excel cập nhật thêm, chạy lại các lệnh này. Nếu sau đó mới import file, truyền đúng hai sổ và sheet như bước 1 để file được gắn vào dòng văn bản đã vào sổ.

### Chỉ cập nhật văn bản đã có

Thêm `--update-only` nếu chỉ muốn đối chiếu kho hiện có, không tạo văn bản thông tin mới. Thêm `--overwrite` để ưu tiên nội dung có trong Excel, kể cả phân loại đến/đi. Ví dụ:

```powershell
php artisan documents:import-ledger "$incomingLedger" --user=3 --direction=incoming --year=2026 --sheet="CVĐ" --update-only --overwrite --dry-run
php artisan documents:import-ledger "$incomingLedger" --user=3 --direction=incoming --year=2026 --sheet="CVĐ" --update-only --overwrite --yes
```

Không cần OCR để đọc hai file Excel. Lệnh `documents:enrich-from-ledger` cũ chỉ bổ sung metadata, không thay cho chức năng vào sổ mới. Muốn dữ liệu xuất ra sổ đầy đủ, dùng `documents:import-ledger`.

## Gửi riêng

Nếu đã tạo Giám đốc/Phó Giám đốc mà không hiện trong danh sách nhận: vào **Quản lý người dùng → Chỉnh sửa**, chọn đúng **Chi nhánh** và **Chức Vụ Thực Tế**, rồi lưu. Model quản trị cũ từng bỏ qua `position_id`, `department_id`, `document_role` lúc tạo mới; phiên bản này đã sửa. Các tài khoản cũ bị mất chức vụ cần gán lại, không tự suy đoán chức vụ từ tên tài khoản/phòng ban. Danh sách ban giám đốc và kiểm tra người nhận ở API cùng dùng chức vụ cấp 1/2 thuộc chi nhánh đang thao tác.

Khi đăng tải chọn mức **Gửi riêng** và chọn Giám đốc/Phó Giám đốc hoặc phòng ban trong chi nhánh. Với văn bản được chuyển đến CN II, văn thư CN II dùng chuyển tiếp nội bộ để chọn ban giám đốc/phòng ban của chính chi nhánh đó. Kiểm tra quyền nhận ở API; không chấp nhận ID người/phòng ban thuộc chi nhánh khác.

Phạm vi gửi riêng hiện được kiểm tra ở danh sách/API văn bản. Kho đính kèm đang dùng đường dẫn public `/storage`; người đã có đường dẫn file vẫn có thể mở trực tiếp. Cần chuyển việc phục vụ file sang đường dẫn có kiểm tra quyền và chặn truy cập trực tiếp kho nếu cần bảo mật nội dung đính kèm theo nơi nhận.

## Ghi và chỉnh sổ thủ công

- **Ghi mới vào sổ:** tạo thông tin văn bản và số sổ, chưa cần file; không gửi thông báo văn bản mới. Sau này vào trang đăng tải tương ứng và tra số để gắn file vào đúng văn bản đã ghi sổ.
- **Đưa văn bản vào sổ:** chỉ tìm văn bản chưa có dòng sổ tại chi nhánh hiện tại, bất kể năm/loại sổ đang chọn. Văn bản đã vào sổ ở chi nhánh khác vẫn có thể được đưa vào sổ đến của chi nhánh mình nếu đã được chuyển đến. Chọn đúng ID để điền thông tin, không tạo văn bản thứ hai. Nếu vừa được người khác ghi sổ trong lúc đang thao tác, hệ thống báo dùng nút bút chì, không tự chuyển thành chỉnh sửa.
- Nút **Chỉnh sửa** ở dòng sổ mở đầy đủ dữ liệu hiện tại. Sổ đến có đủ 10 cột: ngày đến, số đến, tác giả, số/ký hiệu, ngày văn bản, tên loại và trích yếu, đơn vị/người nhận, ngày chuyển, ký nhận, ghi chú. Sổ đi/quyết định hiện thêm người ký, đơn vị/người nhận bản lưu, số lượng bản theo mẫu xuất.
- Ngày văn bản, ngày vào sổ, số/ký hiệu và trích yếu là bắt buộc khi nhập đầy đủ metadata. Số sổ có thể để trống để tự cấp. Tác giả/người ký không bắt buộc. Được giữ số trùng theo sổ gốc. Năm phải khớp ngày vào sổ.
- Quyền chỉnh nội dung giữ như hiện tại: văn thư đăng tải hoặc văn thư cùng chi nhánh với văn bản chưa có file. Với file của người khác/chi nhánh khác, nội dung chỉ đọc; văn thư vẫn chỉnh thông tin vào sổ tại chi nhánh mình theo quyền nhận. Không sửa file, đổi tên file, hoặc mở rộng quyền chỉnh văn bản của chi nhánh khác.
- Lưu sổ và metadata trong cùng transaction; lỗi số/ngày không để lại văn bản tạo dở. Dữ liệu đã lưu được dùng lại khi xuất Excel và tra sổ để đăng file. Ô tùy chọn bị xóa trên form chỉnh sửa sẽ được lưu trống; đây là chỉnh thủ công, khác quy tắc import Excel không ghi đè ô trống.
