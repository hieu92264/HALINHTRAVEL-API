# Dashboard realtime

Dashboard tải dữ liệu chuẩn từ `GET /api/dashboard/overview?date=YYYY-MM-DD`. Reverb chỉ phát event private `dashboard` với `sections` và `occurred_at`; browser sẽ debounce rồi tải lại REST API.

## Cấu hình môi trường

Không commit các giá trị sau. Đặt chúng tại môi trường chạy API và frontend:

```dotenv
# API
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=halinhtravel
REVERB_APP_KEY=<public-key>
REVERB_APP_SECRET=<secret>
REVERB_HOST=api.example.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_ALLOWED_ORIGINS=https://app.example.com

# Frontend
VITE_REVERB_APP_KEY=<public-key>
VITE_REVERB_HOST=api.example.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

`REVERB_ALLOWED_ORIGINS` nhận danh sách origin phân tách bằng dấu phẩy. Không dùng `*` khi triển khai thật.

## Tiến trình chạy

Chạy Reverb và queue worker dưới process manager độc lập với PHP web server:

```powershell
php artisan reverb:start
php artisan queue:work --queue=realtime,default --tries=3 --timeout=90
```

Một Reverb process là đủ cho giai đoạn đầu. Chỉ bật `REVERB_SCALING_ENABLED=true` và dùng Redis trung tâm khi cần nhiều Reverb instance.
