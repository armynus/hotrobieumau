# Khôi phục HTTP cho 10.142.0.15 và giữ IIS

Địa chỉ cuối cùng: **http://10.142.0.15**, không nhập thêm cổng.
Luồng dự kiến: trình duyệt → IIS cổng 80 → Apache tại 127.0.0.1:8080 → Laravel.
Cổng 8080 chỉ mở trên chính server, không mở ra mạng LAN.

## Kết quả kiểm tra đã nhận

Báo cáo lúc 18:13:28 ngày 24/09/2026 trên máy 6500-SR0015 ghi nhận:

- Không có dịch vụ lắng nghe cổng 80. Vì vậy trình duyệt không thể kết nối HTTP tới địa chỉ IP.
- W3SVC và WAS đang chạy, nhưng **Default Web Site đang Stopped**. Dịch vụ IIS chạy không đồng nghĩa website đã Start.
- Không có tiến trình Apache. Chỉ PHP đang nghe tại **127.0.0.1:8000**; địa chỉ này chỉ phục vụ ngay trên server.
- Cấu hình thực tế là `Listen 8080`, trong khi virtual host ứng dụng là `*:80`. Include SSL vẫn bật. Đây chưa phải cấu hình HTTP đồng bộ.
- `Syntax OK` chỉ xác nhận cú pháp. Nó không chứng minh Apache đang chạy hay có nghe cổng 80.
- Log cho thấy Apache từng khởi động được rồi tiến trình cha thoát đột ngột (`AH02538`). Báo cáo chưa chỉ ra nguyên nhân tiến trình cha thoát; không thể quy nguyên nhân này cho cảnh báo chứng chỉ `AH01909`.

## Bước 1 — sửa Apache, chưa thay đổi IIS

1. Giải nén **toàn bộ ZIP** vào một thư mục trên server **10.142.0.15**. Không chạy trực tiếp trong cửa sổ ZIP.
2. Apache cần đang Stop trong XAMPP. Giữ IIS và các website khác hoạt động.
3. Nhấp phải `SUA-APACHE.cmd` → **Run as administrator**.
4. Đợi báo `OK: Apache config is ready...`. Nếu báo `FAILED`, gửi riêng `KET-QUA-SUA-APACHE.txt`; không tiếp tục bước IIS khi Apache chưa sửa thành công.

Công cụ thực hiện:

- Sửa `D:\xampp\apache\conf\httpd.conf`: Listen và ServerName chính sang 127.0.0.1:8080; tắt include `httpd-ssl.conf`.
- Thay đúng virtual host ứng dụng trong `conf\extra\httpd-vhosts.conf`, vẫn dùng `D:/hotrobieumau/public`.
- Giữ `httpd-xampp.conf`, PHP và các module của bản XAMPP đang có trên server.
- Cập nhật APP_URL, ASSET_URL và cookie HTTP trong `.env`; giữ APP_KEY, tài khoản database và SESSION_DRIVER. Chạy `php artisan config:clear`.
- Sao lưu trước khi ghi, kiểm tra `httpd -t` và `httpd -S`, phục hồi nếu kiểm tra hoặc xóa cache cấu hình thất bại. Không tự Start/Stop dịch vụ, không sửa IIS.
- Dừng trước khi ghi nếu phát hiện server khác IP, Apache đang chạy, cổng 8080 đã bị chiếm hoặc có thêm virtual host chưa được kiểm tra.

Đường dẫn sao lưu được in trong báo cáo. Bản sao chứa `.env`, chỉ giữ trên server, không gửi bản sao này trong cuộc trò chuyện. Chỉ gửi các báo cáo kết quả `.txt` khi cần kiểm tra.

## Bước 2 — xác nhận Apache hoạt động

Trong XAMPP Control Panel, nếu mục **Config → Service and Port Settings → Apache → Main Port** còn là 80, đổi thành **8080** để bảng điều khiển khớp cấu hình. Đây là cổng nội bộ của Apache, không phải cổng người dùng nhập.

Bấm **Start Apache** trong XAMPP. Ngay trên server, mở:

`http://127.0.0.1:8080/login_admin`

Trang đăng nhập xuất hiện là kiểm tra được backend. Chưa thử đăng nhập qua địa chỉ nội bộ này: các liên kết/form được cấu hình dùng địa chỉ công khai 10.142.0.15 và còn cần bước IIS bên dưới. Không cần chạy `php artisan serve` để phục vụ website bằng Apache.

Nếu Apache lại tự tắt hoặc không vào được trang nội bộ, chạy `KIEM-TRA-WEB.cmd` và gửi `KET-QUA-KIEM-TRA.txt` cùng `KET-QUA-SUA-APACHE.txt`. Bản cấu hình vượt qua syntax test vẫn có thể gặp lỗi khi chạy trên server; cần log tại thời điểm lỗi để xử lý tiếp.

## Bước 3 — xác định phạm vi IIS trước khi gắn địa chỉ IP

**Cần biết URL của các web IIS đang dùng trước khi áp dụng rule mẫu.** Báo cáo hiện tại chỉ liệt kê Default Web Site và ứng dụng gốc; chưa chỉ ra URL những web khác mà người quản trị muốn giữ.

- Nếu các web khác dùng tên miền riêng hoặc IP/cổng khác: điều kiện Host của rule mẫu có thể tách riêng yêu cầu tới 10.142.0.15.
- Nếu có web ở `http://10.142.0.15/ten-web`: phải thêm ngoại lệ đường dẫn cho web đó trước. Rule mẫu hiện khớp mọi đường dẫn dưới Host 10.142.0.15.
- Nếu web khác cũng cần chính URL gốc `http://10.142.0.15/`: cần chọn URL khác cho một trong hai ứng dụng; một yêu cầu giống hệt nhau không thể tự chọn hai ứng dụng khác nhau.

**`IIS-RULE-TEMPLATE.xml` là bản mẫu để chỉnh theo các URL đó. Chưa áp dụng nó khi chưa xác định phạm vi.**

## Bước 4 — cấu hình IIS sau khi xác định xong phạm vi

Báo cáo chưa phát hiện URL Rewrite/ARR. Kiểm tra trong IIS Manager; nếu chưa có, cài bản x64 **URL Rewrite 2.1 trước**, rồi **Application Request Routing 3.0** từ Microsoft:

- [URL Rewrite 2.1](https://www.iis.net/downloads/microsoft/url-rewrite)
- [Application Request Routing 3.0](https://www.iis.net/downloads/microsoft/application-request-routing)

Đóng/mở lại IIS Manager sau cài đặt. Chọn tên server → **Application Request Routing Cache → Server Proxy Settings → Enable proxy → Apply**. Đây là bước bật khả năng proxy; rule bên dưới mới quyết định yêu cầu nào được chuyển. [Hướng dẫn Microsoft](https://learn.microsoft.com/en-us/iis/extensions/url-rewrite-module/reverse-proxy-with-url-rewrite-v2-and-application-request-routing).

Sao lưu `web.config` của site dự định sửa. Với layout trong báo cáo, site đó là **Default Web Site**. Giữ binding hiện có `http/*:80:`. Trong **URL Rewrite → Add Rule(s) → Blank rule**, tạo rule đã điều chỉnh theo bước 3:

| Mục | Giá trị mẫu |
| --- | --- |
| Name | HotroBieuMau HTTP |
| Requested URL | Matches the Pattern |
| Using | Regular Expressions |
| Pattern | `(.*)` |
| Conditions / Logical grouping | Match All |
| Condition input | `{HTTP_HOST}` |
| Check if input string | Matches the Pattern |
| Condition pattern | `^10\.142\.0\.15(?::80)?$` |
| Action type | Rewrite |
| Rewrite URL | `http://127.0.0.1:8080/{R:1}` |
| Append query string | Chọn |
| Stop processing of subsequent rules | Chọn |

Giữ các rule cũ và kiểm tra thứ tự: rule cũ có thể xử lý yêu cầu trước rule mới. Nếu có web cùng IP theo đường dẫn, thêm ngoại lệ đã xác định ở bước 3. Có thể dùng XML mẫu để đối chiếu; **không ghi đè toàn bộ web.config** bằng XML này. [Tham khảo cấu trúc rule và điều kiện](https://learn.microsoft.com/en-us/iis/extensions/url-rewrite-module/url-rewrite-module-configuration-reference).

Start **Default Web Site** nếu site này vẫn Stopped. Không chạy `iisreset`, không Stop toàn bộ IIS. Apache phải tiếp tục chạy trên 127.0.0.1:8080.

## Bước 5 — kiểm tra từ máy người dùng

Mở đúng **http://10.142.0.15**. Thử đăng nhập, đăng xuất, mở trang có CSS/ảnh, và kiểm tra lại URL của các web IIS khác.

| Hiện tượng | Điểm cần kiểm tra tiếp |
| --- | --- |
| ERR_CONNECTION_REFUSED | IIS/site có Start và có listener cổng 80 chưa |
| Trang IIS mặc định | Rule không được áp dụng, sai site hoặc điều kiện không khớp |
| 502.3 | IIS đã chuyển yêu cầu nhưng không kết nối được Apache/backend |
| 500.19 | Cấu hình IIS, module hoặc quyền đọc cấu hình có lỗi; xem mã chi tiết |
| Trang ứng dụng trả 500 | Đã tới ứng dụng; xem Laravel log để xác định lỗi PHP/database |
| Trên server vào được nhưng máy khác không vào | Kiểm tra tuyến mạng và quy tắc firewall cho HTTP 80 dựa trên kết quả thực tế |

Không mở cổng 8080 ra LAN để xử lý lỗi của bước này. HTTP không dùng chứng chỉ và truyền dữ liệu đăng nhập không mã hóa; cảnh báo "Not secure" trên trình duyệt là phù hợp với chế độ HTTP đã chọn.

## Phục hồi khi cần

Dừng Apache trong XAMPP trước khi phục hồi. Từ thư mục `backup-http-loopback-...` được in trong báo cáo, chép lại `httpd.conf`, `httpd-vhosts.conf` vào đúng vị trí cũ, và `laravel.env` về `D:\hotrobieumau\.env`. Sau đó chạy `D:\xampp\php\php.exe artisan config:clear` tại `D:\hotrobieumau`.

Nếu đã thêm rule IIS, chỉ Disable/Delete rule **HotroBieuMau HTTP** hoặc phục hồi bản web.config đã sao lưu. Không xóa các rule hay binding của web khác. Cấu hình trước sửa cũng đang lỗi kết nối; phục hồi chỉ trả về trạng thái trước sửa.

## Phạm vi xác minh

Đã kiểm tra bằng Apache cục bộ trong tiến trình riêng, cổng 18080: cú pháp, virtual host, khởi động, PHP, URL Laravel, chuyển hướng Apache, GET/POST và chặn alias quản trị XAMPP qua backend. Đã kiểm tra patch giữ nguyên các giá trị .env không liên quan và từ chối ghi khi có virtual host khác.

Chưa chạy công cụ sửa trên server 10.142.0.15, chưa kiểm tra IIS/ARR thực tế, chưa kiểm tra đăng nhập với database của server. Apache cục bộ dùng để thử là 2.4.56; server báo 2.4.58. Script sẽ kiểm tra lại bằng đúng Apache trên server trước khi báo thành công.
