# Lưu trữ phiếu Hỗ trợ IT

- Tệp mới được nhận vào `storage/app/private/it-support/CHO-XU-LY/PHIEU-{id}`. Chúng không dùng `public/storage`.
- Lần đầu phiếu chuyển sang **Đã hoàn thành** hoặc **Đã đóng**, `completed_at` được cố định. Tệp và bản ghi `PHIEU-{id}.json` được lưu trong `storage/app/private/it-support/NAM YYYY/THANG MM-YYYY/NGAY DD-MM-YYYY/PHIEU-{id}`. Bản ghi gồm nội dung, kết quả, người gửi, tên tệp và lịch sử phản hồi. Phản hồi về sau cập nhật bản ghi này.
- Màn hình người dùng chỉ mở phiếu và tải tệp của chính mình; màn hình quản trị dùng phiên quản trị. Tải tệp luôn qua route kiểm tra quyền.
- Khi nâng cấp nơi đã có tệp cũ trong `storage/app/public/it_supports`, chạy `php artisan migrate --path=database/migrations/main --force` rồi `php artisan it-support:secure-archive --execute`. Lệnh sao chép sang kho riêng, cập nhật đường dẫn và chỉ xóa bản công khai sau khi ghi DB thành công. Chạy lại an toàn nếu bị gián đoạn. Không xóa tệp cũ thủ công trước khi lệnh báo `lỗi: 0`.
- Sao lưu cả **cơ sở dữ liệu** và `storage/app/private/it-support`; mỗi phần riêng lẻ không đủ để khôi phục đầy đủ hồ sơ.
