# HTTP cổng 80: http://10.142.0.15/

Bộ cấu hình này dùng HTTP cổng mặc định 80, nên người dùng chỉ nhập **http://10.142.0.15/**. HTTPS đã tắt; không cần chứng chỉ. Thư mục ứng dụng là `D:/hotrobieumau/public`, XAMPP ở `D:/xampp`.

## Áp dụng trên server

1. Sao lưu cấu hình Apache và `.env` hiện tại. Stop Apache trong XAMPP.
2. Chép các file sau từ gói vào máy đích:

| File trong gói | Đích |
| --- | --- |
| `conf/httpd.conf` | `D:\xampp\apache\conf\httpd.conf` |
| `conf/extra/httpd-vhosts.conf` | `D:\xampp\apache\conf\extra\httpd-vhosts.conf` |
| `conf/extra/httpd-ssl.conf` | `D:\xampp\apache\conf\extra\httpd-ssl.conf` |

`httpd-xampp.conf` được kèm để đối chiếu, không đổi so với bản người dùng đã gửi. Giữ cấu hình PHP đang hoạt động trên server nếu phiên bản hoặc vị trí XAMPP đã thay đổi.

3. Sửa/thêm các dòng sau trong `D:\hotrobieumau\.env`, mỗi khóa chỉ có một dòng. Không thay toàn bộ file .env và không đổi APP_KEY hoặc thông tin database:

```dotenv
APP_URL=http://10.142.0.15
ASSET_URL=http://10.142.0.15
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=null
SESSION_SAME_SITE=lax
SESSION_PARTITIONED_COOKIE=false
SESSION_COOKIE=hotrobieumau_http_session
```

4. Mở PowerShell trên server, chạy:

```powershell
Set-Location -LiteralPath 'D:\hotrobieumau'
& 'D:\xampp\php\php.exe' artisan config:clear
& 'D:\xampp\apache\bin\httpd.exe' -t -f 'D:/xampp/apache/conf/httpd.conf'
& 'D:\xampp\apache\bin\httpd.exe' -S -f 'D:/xampp/apache/conf/httpd.conf'
```

Lệnh Laravel phải thành công. Apache phải trả `Syntax OK` và virtual host cổng 80 cho `10.142.0.15`.

5. Start Apache, mở **http://10.142.0.15/** và đăng nhập. Trong XAMPP Control Panel, đặt Main Port của Apache là 80 nếu trước đó đã đổi nút Admin sang một cổng khác.

## Nội dung cấu hình chính

Trong httpd.conf:

```apache
Listen 80
ServerName 10.142.0.15:80
Include conf/extra/httpd-vhosts.conf
#Include conf/extra/httpd-ssl.conf
```

Virtual host dùng `<VirtualHost *:80>`, `ServerName 10.142.0.15` và `DocumentRoot "D:/hotrobieumau/public"`. Module SSL đã tắt và file httpd-ssl.conf chỉ còn comment. Cổng `:80` trong ServerName là cổng HTTP mặc định; người dùng không phải gõ nó trong URL.

## Nếu chưa truy cập được

- Cổng 80 trên server phải khả dụng khi Apache khởi động. Nếu một dịch vụ khác đang dùng, xác định dịch vụ trước khi thay cấu hình hoặc dừng nó:

```powershell
Get-NetTCPConnection -State Listen -LocalPort 80 -ErrorAction SilentlyContinue |
    Select-Object LocalAddress,LocalPort,OwningProcess
```

- Nếu chạy được trên server nhưng máy khác không vào được, kiểm tra firewall cho TCP 80 trong phạm vi mạng sử dụng.
- Xóa cookie/dữ liệu trang của riêng `10.142.0.15` để tránh cookie HTTPS cũ gây lỗi đăng nhập/419.
- Gõ đầy đủ `http://10.142.0.15/`. Nếu trình duyệt tự đổi sang HTTPS, kiểm tra chế độ HTTPS-Only và thêm ngoại lệ riêng cho trang này nếu được phép.
- Nếu server trả redirect sang HTTPS hoặc cổng cũ, kiểm tra rule redirect trong `.htaccess`, provider, middleware, proxy và cache cấu hình trên production. Mã nguồn local đã kiểm tra không có đoạn ép HTTPS.
- Log chung: `D:\xampp\apache\logs\error.log`. Log virtual host: `D:\xampp\apache\logs\hotrobieumau-http-error.log`.

## Phạm vi xác minh

Kết quả kiểm tra cấu hình được lưu trong `VALIDATION.txt`. Các include mặc định ngoài bốn file được gửi dùng bộ XAMPP local để kiểm tra. Không thay cấu hình đang chạy hay restart Apache trên production. Kiểm tra cú pháp không chứng minh cổng 80 trên production đang trống hoặc login production đã hoạt động.

HTTP không có bước xác minh chứng chỉ HTTPS, nhưng vẫn có thể chịu các chính sách kiểm tra khác của Kaspersky. Dữ liệu trên kết nối HTTP không được TLS mã hóa.
