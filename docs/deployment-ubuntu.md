# Deploy production trên Ubuntu VPS

API production chạy tại `https://api.halinh-travel.io.vn`; frontend được phép gọi API và Reverb từ `https://halinh-travel.io.vn`.

## 1. Chuẩn bị VPS và DNS

1. Cài Docker Engine và Docker Compose plugin theo hướng dẫn chính thức của Docker cho Ubuntu.
2. Mở firewall cho SSH, HTTP và HTTPS; không mở MySQL, Redis hoặc Reverb:

   ```sh
   sudo ufw allow OpenSSH
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```

3. Đặt timezone để backup chạy theo giờ Việt Nam:

   ```sh
   sudo timedatectl set-timezone Asia/Ho_Chi_Minh
   ```

4. Tạo A record `api.halinh-travel.io.vn` trỏ về IPv4 VPS. Chờ bản ghi có hiệu lực trước khi cấp chứng chỉ.
5. Clone source vào một thư mục cố định, ví dụ `/opt/halinhtravel-api`.

## 2. Tạo cấu hình bí mật

```sh
cd /opt/halinhtravel-api
cp .env.production.example .env.production
chmod 600 .env.production
```

Điền giá trị ngẫu nhiên, riêng biệt cho mật khẩu MySQL/Redis, `APP_KEY`, `JWT_SECRET` và ba giá trị `REVERB_APP_*`. Có thể lấy hai secret Laravel mà không ghi vào file bằng:

```sh
docker run --rm php:8.2-cli php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
docker run --rm php:8.2-cli php -r 'echo bin2hex(random_bytes(32)).PHP_EOL;'
```

Không commit `.env.production`. Thay và xoay ngay SMTP app password từng xuất hiện trong lịch sử repository.

Các lệnh Compose dưới đây luôn dùng `--env-file .env.production`; điều này giúp MySQL nhận đúng biến `DB_*` mà không dùng `.env` local.

## 3. Cấp chứng chỉ Let’s Encrypt lần đầu

Tạo image Nginx, khởi động Nginx bootstrap chỉ phục vụ ACME challenge, rồi yêu cầu chứng chỉ:

```sh
docker compose --env-file .env.production -f compose.production.yaml build nginx
docker compose --env-file .env.production -f compose.production.yaml --profile bootstrap up -d nginx-bootstrap
docker compose --env-file .env.production -f compose.production.yaml --profile tools run --rm certbot certonly --webroot -w /var/www/certbot -d api.halinh-travel.io.vn --email "YOUR_ACME_EMAIL" --agree-tos --no-eff-email
docker compose --env-file .env.production -f compose.production.yaml --profile bootstrap rm -sf nginx-bootstrap
```

Thay `YOUR_ACME_EMAIL` bằng địa chỉ quản trị thật. Nếu Certbot báo lỗi, kiểm tra A record và chắc chắn cổng 80 không bị dịch vụ khác chiếm.

## 4. Khởi động và cập nhật

Khởi động toàn bộ services:

```sh
docker compose --env-file .env.production -f compose.production.yaml up -d --build
```

Không migration tự động. Sau khi tạo backup và đã chấp thuận thay đổi schema, chạy riêng:

```sh
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan migrate --force
```

Các lần phát hành tiếp theo:

```sh
git pull --ff-only
docker compose --env-file .env.production -f compose.production.yaml up -d --build --remove-orphans
```

Kiểm tra:

```sh
docker compose --env-file .env.production -f compose.production.yaml ps
curl -I https://api.halinh-travel.io.vn/up
```

## 5. Backup và gia hạn chứng chỉ

Tạo thử backup:

```sh
chmod +x scripts/backup-mysql.sh
./scripts/backup-mysql.sh
```

Thêm cron bằng `crontab -e` cho user có quyền Docker:

```cron
30 2 * * * /opt/halinhtravel-api/scripts/backup-mysql.sh >> /opt/halinhtravel-api/backups/backup.log 2>&1
17 3 * * * cd /opt/halinhtravel-api && docker compose --env-file .env.production -f compose.production.yaml --profile tools run --rm certbot renew --webroot -w /var/www/certbot && docker compose --env-file .env.production -f compose.production.yaml exec -T nginx nginx -s reload >> /opt/halinhtravel-api/backups/certbot.log 2>&1
```

MySQL dump được giữ 30 ngày tại chính VPS. Backup này không bảo vệ trước mất toàn bộ VPS; cần đưa database dump và `app_storage` sang object storage hoặc máy khác ở giai đoạn tiếp theo.

## 6. Chuyển sang Cloudflare sau này

Để SSL/TLS ở **Full (strict)**, giữ chứng chỉ Let’s Encrypt tại origin và chỉ proxy hostname API. Khi đó bổ sung Cloudflare trusted proxy ranges/real client IP cho Nginx và Laravel để rate limit nhận đúng IP người dùng.
