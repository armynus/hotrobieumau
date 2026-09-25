# Chạy trực tiếp http://10.142.0.15 bằng Apache

Bản này dùng **Apache cổng 80**, không cần IIS, ARR, cổng 8080 hoặc `php artisan serve`.
Áp dụng trên server **10.142.0.15**, XAMPP tại **D:\xampp**, ứng dụng tại **D:\hotrobieumau**.

## Làm theo thứ tự này

1. Giải nén toàn bộ ZIP vào một thư mục mới trên server. Dùng file **SUA-HTTP80.cmd** trong gói này.
2. Giữ IIS không sử dụng cổng 80. Trong XAMPP, Apache cần đang **Stop** khi sửa cấu hình.
3. Nhấp phải **SUA-HTTP80.cmd → Run as administrator**.
4. Đợi dòng **OK - APACHE HTTP 80 CONFIGURED.** Nếu xuất hiện `FAILED`, gửi file **KET-QUA-HTTP80.txt**; công cụ không coi đó là đã sửa thành công.
5. Mở XAMPP Control Panel. Nếu trước đó đã đặt **Config → Service and Port Settings → Apache → Main Port** là 8080, đổi lại **80** và Save. Bấm **Start Apache**.
6. Ngay trên server, mở **http://10.142.0.15**. Sau đó thử cùng địa chỉ từ máy người dùng. Không thêm `:8080` hoặc `:8000`.
7. Chạy **KIEM-TRA-HTTP80.cmd** để ghi nhận kết quả đang chạy. Nếu còn lỗi, gửi **KET-QUA-CHAY-HTTP80.txt** cùng **KET-QUA-HTTP80.txt**.

Công cụ sửa chỉ chuẩn bị cấu hình; thao tác Start Apache ở bước 5 là bắt buộc. `Syntax OK` không có nghĩa dịch vụ đã chạy.

## Công cụ sửa gì

- Sao lưu file trước khi sửa tại `D:\xampp\apache\conf\backup-direct-http80-...`. Đường dẫn cụ thể được ghi trong báo cáo.
- Đổi listener chính thành `Listen 80`, ServerName thành `10.142.0.15:80` và tắt include SSL chuẩn của XAMPP.
- Đặt lại toàn bộ `httpd-vhosts.conf` thành **một website** tại `*:80`, DocumentRoot `D:/hotrobieumau/public`. Vhost cũ nằm trong bản sao lưu. Đây là thay đổi có chủ đích theo yêu cầu chỉ đưa ứng dụng này lên Apache.
- Giữ file `httpd-xampp.conf` và cấu hình nạp PHP đang dùng trên server. Bật mod_rewrite/mod_headers từ khai báo module chuẩn nếu chúng đang bị comment.
- Đặt APP_URL, ASSET_URL thành `http://10.142.0.15`, điều chỉnh cookie cho HTTP và xóa cache cấu hình Laravel. Không thay APP_KEY, thông tin database hoặc SESSION_DRIVER.
- Kiểm tra bằng đúng `httpd.exe` trên server: cú pháp, module PHP và virtual host. Nếu các bước kiểm tra hoặc xóa cache cấu hình thất bại, khôi phục file cũ.
- Kiểm tra cổng 80 trước khi ghi. Nếu đã có chương trình chiếm cổng, dừng và ghi thông tin listener/PID vào báo cáo.
- Không tự Start/Stop IIS hay Apache, không thay firewall, không xóa chứng chỉ.

Apache chỉ phục vụ cổng đã cấu hình bằng `Listen`; virtual host phải khớp cổng đó. Khi đổi listener, cần Stop rồi Start Apache. [Tài liệu Apache](https://httpd.apache.org/docs/2.4/bind.html).

## Lỗi trong báo cáo vừa gửi

`Expected one application VirtualHost for 10.142.0.15...` là điều kiện kiểm tra của công cụ cũ. Công cụ đó dừng trước khi sao lưu/ghi file vì cấu hình virtual host không khớp giả định của nó; đây không phải thông báo lỗi cú pháp của Apache.

Bản mới không dùng điều kiện nhận diện virtual host cũ đó. Nó sao lưu rồi đặt lại file vhost cho một ứng dụng, đúng với yêu cầu dùng Apache trực tiếp trên cổng 80.

## Nếu vẫn không mở được

Chạy `KIEM-TRA-HTTP80.cmd` **sau khi bấm Start Apache**. Báo cáo sẽ chứa tiến trình Apache, listener cổng 80, mã HTTP trả về, kết quả kiểm tra cấu hình, log Apache gần nhất và lỗi Windows liên quan đến Apache trong 4 giờ gần nhất.

- `CONNECTION FAILED` và không có listener cổng 80: Apache chưa chạy hoặc đã thoát; xem log và sự kiện trong báo cáo.
- Cổng 80 đã bị chương trình khác chiếm: gửi báo cáo có PID để xác định đúng dịch vụ. Không dừng tiến trình `System` hay dịch vụ `HTTP` bằng cách đoán.
- HTTP 500: đã kết nối được web server; cần xem lỗi ứng dụng/PHP/database tiếp theo.
- HTTP 301/302: có phản hồi chuyển hướng; xem dòng `Location` để biết nó chuyển tới đâu.
- Trên server vào được, máy khác không vào: kiểm tra mạng/firewall theo kết quả thực tế. Cổng 80 từng phục vụ IIS trên máy này nên chưa có cơ sở kết luận firewall đang chặn.

Giữ IIS không lấy lại cổng 80 khi Apache phục vụ cổng này; kiểm tra lại sau khi khởi động lại server nếu IIS đang được đặt tự chạy. Cài Apache làm Windows service là lựa chọn cho chạy nền ổn định sau đăng xuất/khởi động lại; gói này chưa thay cách cài dịch vụ. [Tài liệu Apache trên Windows](https://httpd.apache.org/docs/2.4/platform/windows.html).

## Sao lưu và phục hồi

Bản sao lưu chứa `.env`; không gửi thư mục sao lưu vì có thông tin database và APP_KEY. Chỉ gửi hai báo cáo kết quả `.txt` nếu cần kiểm tra.

Để phục hồi: Stop Apache, lấy `httpd.conf` và `httpd-vhosts.conf` trong thư mục backup chép lại đúng vị trí cũ. Chép `laravel.env` về `D:\hotrobieumau\.env`. Sau đó tại `D:\hotrobieumau`, chạy:

```powershell
& 'D:\xampp\php\php.exe' artisan config:clear
```

Phục hồi chỉ trả về cấu hình trước sửa, vốn chưa đáp ứng yêu cầu truy cập hiện tại.

## Kết quả thử trước khi giao

Đã thử bằng Apache cục bộ, giữ riêng config/PID/log và cổng thử 18080. Cấu hình sản phẩm được kiểm tra `*:80`; chỉ lượt chạy thử được chuyển sang loopback 18080 để không chiếm cổng đang dùng. Đã kiểm tra PHP, rewrite bằng `.htaccess` của ứng dụng, GET/POST, URL/redirect HTTP không kèm cổng, chặn truy cập trực tiếp các đường dẫn tài liệu bị hạn chế và các alias quản trị XAMPP.

Đã kiểm tra giữ nguyên các giá trị .env không liên quan, xử lý trùng APP_URL, chạy patch lặp lại, include tuyệt đối/biến SRVROOT và loại listener dư. Chưa chạy bản sửa này trên server 10.142.0.15; chưa kiểm tra đăng nhập với database trên server. Công cụ sẽ kiểm tra lại bằng Apache thực tế của server trước khi báo OK.
