# Chuyển ứng dụng sang HTTP cổng 8080

Địa chỉ dùng sau khi áp dụng: **http://10.142.0.15:8080/**.

Gói này giữ cổng 8080 theo cấu hình đã gửi. Nó giả định XAMPP ở `D:/xampp`, ứng dụng ở `D:/hotrobieumau`, và máy đích có IP `10.142.0.15`.

HTTP không dùng chứng chỉ nên không có cảnh báo chứng chỉ HTTPS cho kết nối HTTP đó. Kaspersky vẫn có thể kiểm tra hoặc chặn theo các chính sách khác. HTTP không mã hóa mật khẩu và cookie phiên trên đường truyền.

## 1. Sao lưu trên server

Sao lưu thư mục `D:\xampp\apache\conf` và file `D:\hotrobieumau\.env` trước khi thay. Giữ bản sao .env ở nơi riêng, ngoài thư mục public và ngoài kho Git, vì có thông tin truy cập hệ thống. Giữ lại chứng chỉ cũ để có thể quay về HTTPS.

## 2. Chép cấu hình Apache

Stop Apache trong XAMPP trên máy đích, rồi chép các file sau từ gói giải nén:

| Trong gói | Đích |
| --- | --- |
| `conf/httpd.conf` | `D:\xampp\apache\conf\httpd.conf` |
| `conf/extra/httpd-vhosts.conf` | `D:\xampp\apache\conf\extra\httpd-vhosts.conf` |
| `conf/extra/httpd-ssl.conf` | `D:\xampp\apache\conf\extra\httpd-ssl.conf` |

`httpd-xampp.conf` được đóng gói để đối chiếu; nó giống hệt bản đã gửi trước đó. Nếu server đang nạp PHP bình thường, giữ file hiện có. Nếu PHP/XAMPP trên server đã đổi phiên bản hoặc vị trí, dùng cấu hình PHP tương ứng của server.

Gói mới có các thay đổi:

- `Listen 8080` và `ServerName 10.142.0.15:8080` trong file chính.
- Giữ `Include conf/extra/httpd-vhosts.conf` hoạt động.
- Comment `Include conf/extra/httpd-ssl.conf` và module SSL; file SSL mới chỉ chứa comment, không có cổng 443 hay chứng chỉ.
- Tạo một virtual host HTTP ở cổng 8080, trỏ vào `D:/hotrobieumau/public` và cho phép `.htaccess` xử lý route Laravel.
- Không cần xóa file `.pem` hoặc private key.

Nếu server có các website khác cùng chạy trên Apache, đối chiếu và giữ các virtual host của chúng trước khi thay toàn bộ file.

## 3. Sửa .env của ứng dụng trên server

Mở `D:\hotrobieumau\.env`. Sửa hoặc thêm các dòng dưới, mỗi khóa chỉ xuất hiện một lần. Đây không phải toàn bộ file .env; giữ nguyên APP_KEY, DB và các thiết lập khác.

```dotenv
APP_URL=http://10.142.0.15:8080
ASSET_URL=http://10.142.0.15:8080
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=null
SESSION_SAME_SITE=lax
SESSION_PARTITIONED_COOKIE=false
SESSION_COOKIE=hotrobieumau_http_session
```

`SESSION_SECURE_COOKIE=false` cho phép gửi cookie đăng nhập qua HTTP. `SESSION_DOMAIN=null` tạo cookie cho đúng host hiện tại; không đặt scheme hoặc port trong SESSION_DOMAIN. Tên cookie mới tách phiên HTTP khỏi cookie HTTPS cũ. Người dùng cần đăng nhập lại.

Trong bản mã nguồn local đã kiểm tra, `config/session.php` đọc được các biến này và không thấy `URL::forceScheme('https')` hay rewrite ép HTTPS trong `public/.htaccess`. Nếu mã trên production khác, bỏ riêng đoạn ép HTTPS nếu có; giữ lại các rule route và kiểm tra quyền truy cập file.

Mở PowerShell trên server để xóa cấu hình Laravel đã cache:

```powershell
Set-Location -LiteralPath 'D:\hotrobieumau'
& 'D:\xampp\php\php.exe' artisan config:clear
```

Lệnh phải thành công. Khi cấu hình đã cache, thay `.env` chưa đủ để Laravel dùng giá trị mới.

## 4. Kiểm tra Apache rồi khởi động

```powershell
& 'D:\xampp\apache\bin\httpd.exe' -t -f 'D:/xampp/apache/conf/httpd.conf'
& 'D:\xampp\apache\bin\httpd.exe' -S -f 'D:/xampp/apache/conf/httpd.conf'
```

Lệnh đầu phải trả `Syntax OK`. Lệnh sau phải có virtual host cổng 8080 cho `10.142.0.15` và không còn HTTPS virtual host ở cổng 443 từ bộ cấu hình này.

Sau đó Start Apache trong XAMPP. Trong XAMPP Control Panel, có thể đặt Main Port của Apache là 8080 để nút Admin mở đúng cổng; chính directive Listen mới quyết định Apache lắng nghe cổng nào.

## 5. Truy cập và kiểm tra login

Gõ đầy đủ **http://10.142.0.15:8080/**. Không dùng bookmark HTTPS cũ, không dùng `https://10.142.0.15:8080`.

- Xóa cookie và dữ liệu trang của riêng `10.142.0.15`, rồi đăng nhập lại. Điều này tránh cookie Secure/XSRF cũ gây lỗi 419 hoặc vòng lặp đăng nhập.
- Nếu Firefox/Chrome tự nâng thành HTTPS, kiểm tra chế độ HTTPS-Only/Always use secure connections và thêm ngoại lệ riêng cho địa chỉ HTTP này nếu chính sách trình duyệt cho phép.
- Nếu HTTP trả redirect sang HTTPS, kiểm tra `.htaccess`, service provider, middleware và proxy ở production. Đổi APP_URL không tự gỡ một rule redirect riêng.
- Nếu không truy cập được từ máy khác, kiểm tra firewall cho TCP 8080 trên server. Không tắt toàn bộ firewall.
- Nếu Apache không start dù `Syntax OK`, kiểm tra cổng 8080 đang bị tiến trình nào giữ. Kiểm tra cú pháp không phát hiện xung đột cổng:

```powershell
Get-NetTCPConnection -State Listen -LocalPort 8080 -ErrorAction SilentlyContinue |
    Select-Object LocalAddress,LocalPort,OwningProcess
```

Log chung: `D:\xampp\apache\logs\error.log`. Log ứng dụng HTTP: `D:\xampp\apache\logs\hotrobieumau-http-error.log`.

## Nếu muốn URL không có :8080

Chỉ đổi sang cổng 80 sau khi kiểm tra cổng 80 còn trống. Đổi đồng bộ:

- `Listen 8080` thành `Listen 80` trong httpd.conf.
- `ServerName 10.142.0.15:8080` thành `ServerName 10.142.0.15:80` trong httpd.conf.
- `<VirtualHost *:8080>` thành `<VirtualHost *:80>` trong httpd-vhosts.conf.
- APP_URL và ASSET_URL thành `http://10.142.0.15`.

Chạy lại `artisan config:clear`, kiểm tra `httpd -t`, restart Apache và cập nhật quy tắc firewall tương ứng nếu cần.

## Phạm vi kiểm tra

Đã chạy Apache 2.4.56 local với bộ cấu hình trong gói: `-t` trả `Syntax OK`; `-S` xác nhận virtual host `10.142.0.15` ở cổng 8080; kiểm tra module xác nhận PHP và rewrite được nạp, SSL không được nạp. Không dùng chứng chỉ thay thế trong kiểm tra này. Kết quả được lưu tại `VALIDATION.txt`.

Nguồn là bản cấu hình đã lưu từ lần kiểm tra trước tại `output/apache-config-review-20260924/fixed`; đường dẫn `E:/modify` không còn truy cập được ở lần này. Các include XAMPP khác không được gửi kèm được kiểm tra bằng bộ XAMPP local. Cần chạy lại kiểm tra trên server đích trước khi khởi động.

Gói này không chứa .env thật, thông tin đăng nhập hay khóa chứng chỉ. Chưa thay cấu hình đang chạy hoặc khởi động lại Apache trên production. Không thể xác nhận login trên production chỉ bằng kiểm tra cú pháp Apache.

## Tài liệu tham khảo

- Apache Listen và VirtualHost: https://httpd.apache.org/docs/2.4/vhosts/details.html
- Laravel configuration cache: https://laravel.com/docs/11.x/configuration#configuration-caching
- Cookie Secure: https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie#secure
