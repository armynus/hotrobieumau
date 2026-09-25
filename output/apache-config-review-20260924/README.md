# Sửa cấu hình Apache/XAMPP

Bộ này giữ đích trong file đã gửi: XAMPP ở `D:/xampp`, ứng dụng ở `D:/hotrobieumau/public`, HTTPS cho `10.142.0.15:443`, HTTP ở cổng `8080`. Chỉ tạo bản sửa để chép lên máy đích; chưa áp dụng hay restart Apache trên production.

## Lỗi đã xác nhận

File gốc `extra/httpd-ssl.conf` mở `<VirtualHost _default_:443>` ở dòng 121 nhưng chưa đóng đã mở `<VirtualHost *:443>` ở dòng 291. Chạy Apache 2.4.56 trên bản kiểm tra dùng đúng ba file include đã gửi cho kết quả:

```text
httpd-ssl.conf:121: <VirtualHost> was not closed.
APACHE_EXIT_CODE=1
```

Sửa bắt buộc: thêm `</VirtualHost>` ngay trước `<VirtualHost *:443>` của `10.142.0.15`. Đặt thêm ở cuối file sẽ không giải quyết việc hai khối bị lồng nhau.

## Những thay đổi trong thư mục fixed

- `extra/httpd-ssl.conf`: thêm đúng thẻ đóng nói trên; giữ nguyên đường dẫn chứng chỉ production.
- `httpd.conf`: đổi `ServerName localhost:80` thành `ServerName localhost:8080` để đồng bộ với `Listen 8080`. Việc lệch này không phải lỗi cú pháp gây dừng đã tái hiện.
- `httpd.conf`: comment dòng `LoadModule cache_disk_module modules/mod_cache_disk.so` đang bật ở dòng 138. Bốn file được gửi không cấu hình sử dụng disk cache, và `cache_module` đang tắt. Đây là dọn cấu hình phụ thuộc, không phải lỗi cú pháp mà Apache vừa báo. Nếu server có cấu hình cache ở file khác, cần giữ module và nạp `cache_module` trước nó theo cấu hình cache thực tế.
- `extra/httpd-xampp.conf` và `extra/httpd-vhosts.conf`: giữ nguyên từng byte. File vhosts hiện chỉ có comment, không có HTTP virtual host nào đang hoạt động.

Tham khảo phụ thuộc cache: https://httpd.apache.org/docs/2.4/mod/mod_cache_disk.html

## Cách áp dụng trên máy đang lỗi

1. Sao lưu `D:\xampp\apache\conf\httpd.conf` và `D:\xampp\apache\conf\extra\httpd-ssl.conf`.
2. Chép `fixed\httpd.conf` vào `D:\xampp\apache\conf\httpd.conf`.
3. Chép `fixed\extra\httpd-ssl.conf` vào `D:\xampp\apache\conf\extra\httpd-ssl.conf`.
4. Trong PowerShell trên máy đích, kiểm tra hai file chứng chỉ:

```powershell
Test-Path -LiteralPath 'D:\xampp\apache\conf\ssl\10.142.0.15.pem' -PathType Leaf
Test-Path -LiteralPath 'D:\xampp\apache\conf\ssl\10.142.0.15-key.pem' -PathType Leaf
```

Cả hai phải trả `True`. File còn phải có nội dung hợp lệ và certificate/key phải khớp. Nếu thiếu, khôi phục đúng cặp chứng chỉ của máy đích hoặc sửa đường dẫn đến cặp chứng chỉ hợp lệ đang có.

5. Kiểm tra toàn bộ cấu hình đã đặt tại đúng vị trí:

```powershell
& 'D:\xampp\apache\bin\httpd.exe' -t -f 'D:/xampp/apache/conf/httpd.conf'
& 'D:\xampp\apache\bin\httpd.exe' -S -f 'D:/xampp/apache/conf/httpd.conf'
```

Lệnh đầu phải trả `Syntax OK`. Sau đó Start Apache trong XAMPP. Nếu vẫn dừng, xem lỗi mới trong `D:\xampp\apache\logs\error.log`; kiểm tra tiến trình đang giữ cổng bằng:

```powershell
Get-NetTCPConnection -State Listen -LocalPort 8080,443 -ErrorAction SilentlyContinue |
    Select-Object LocalAddress,LocalPort,OwningProcess
```

`-t` không xác nhận cổng còn trống. Không cần đổi cổng chỉ vì thông báo chung trong XAMPP.

## Phạm vi đã kiểm tra

- Tái hiện lỗi thiếu thẻ đóng bằng Apache thật: exit 1.
- Sau khi sửa thẻ đóng, Apache báo thiếu `D:/xampp/apache/conf/ssl/10.142.0.15.pem` trên máy local hiện tại. Chưa có bằng chứng file này thiếu trên production.
- Trong bản kiểm tra riêng, thay hai đường dẫn chứng chỉ production bằng cặp mặc định có sẵn của XAMPP trên local. Bản cuối trả `Syntax OK` (exit 0); `-S` nhận đủ hai HTTPS virtual host (exit 0).
- Các include XAMPP khác không được gửi kèm được lấy từ bộ XAMPP local. Vì vậy vẫn phải chạy `-t` trên máy đích.
- Bản `fixed` trong gói ZIP vẫn dùng chứng chỉ production; không đóng gói chứng chỉ hoặc private key. Không chép các file trong thư mục `validation` lên server.

## Địa chỉ truy cập

- HTTPS của ứng dụng theo cấu hình này: `https://10.142.0.15/` trên mạng truy cập được server.
- HTTP cổng `8080` hiện dùng `D:/xampp/htdocs`, vì file vhosts không có khối hoạt động; nó chưa trỏ vào `D:/hotrobieumau/public`.
- Nếu đem chạy local, cần cấu hình hostname/IP, chứng chỉ và đường dẫn phù hợp với local; không thể suy ra các giá trị đó chỉ từ file production.
