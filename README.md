# Ha Linh Travel API

Hướng dẫn phân quyền role/permission: [docs/authorization.md](docs/authorization.md).

Laravel API chạy local bằng Docker với Nginx, PHP-FPM, MySQL và Redis.

## Yêu cầu

- Git
- Docker Desktop đang chạy, sử dụng Linux containers

Không cần cài PHP, Composer, MySQL, Redis hoặc Node.js trên Windows.

## Cài đặt lần đầu

Mở PowerShell trong thư mục bạn muốn lưu source, sau đó chạy:

```powershell
git clone <GIT_REPOSITORY_URL>
cd halinhtravel-api
Copy-Item .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Thay `<GIT_REPOSITORY_URL>` bằng URL GitHub của dự án. Lệnh `--build` chỉ cần dùng lần đầu hoặc khi thay đổi Dockerfile/Compose.

Nếu dự án có thay đổi frontend Vite, chạy thêm:

```powershell
docker compose exec app npm install
docker compose exec app npm run build
```

## Khởi chạy dự án hằng ngày

1. Mở Docker Desktop và chờ trạng thái **Engine running**.
2. Mở terminal tại thư mục dự án.
3. Chạy:

```powershell
docker compose up -d
```

Kiểm tra containers:

```powershell
docker compose ps
```

Truy cập các dịch vụ:

| Dịch vụ | Địa chỉ |
| --- | --- |
| Laravel API | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| MySQL từ Navicat / DBeaver | `127.0.0.1:3307` |
| Redis từ máy Windows | `127.0.0.1:6380` |

## Dừng dự án

Để dừng containers nhưng giữ nguyên MySQL và Redis:

```powershell
docker compose down
```

Lần sau chỉ cần mở Docker Desktop và chạy lại:

```powershell
docker compose up -d
```

## Các lệnh thường dùng

```powershell
# Theo dõi log của tất cả services
docker compose logs -f

# Log riêng Laravel/PHP
docker compose logs -f app

# Chạy Artisan trong container PHP
docker compose exec app php artisan migrate
docker compose exec app php artisan route:list

# Cài PHP package bằng Composer
docker compose exec app composer require vendor/package

# Build lại image khi sửa Dockerfile hoặc compose.yaml
docker compose up -d --build
```

Bạn vẫn sửa code trực tiếp bằng IDE như bình thường. Source trên máy được mount vào container nên chỉ cần refresh API sau khi lưu file PHP.

## Kết nối MySQL bằng Navicat

```text
Host: 127.0.0.1
Port: 3307
Database: halinhtravel_db
Username: halinhtravel_user
Password: secret
```

Nếu bạn đã thay đổi các biến `DB_*` trong `.env`, dùng thông tin tương ứng. Không dùng host `db` trong Navicat: tên này chỉ có hiệu lực bên trong Docker network.

## Làm mới toàn bộ database local

> Cảnh báo: lệnh dưới đây xóa toàn bộ dữ liệu MySQL và Redis của dự án.

```powershell
docker compose down -v
docker compose up -d --build
docker compose exec app php artisan migrate
```

## Khắc phục sự cố

**Docker báo không kết nối được Engine**

Mở Docker Desktop, chờ **Engine running**, rồi chạy lại lệnh Docker.

**Cổng 8080 hoặc 3307 đã được dùng**

Đổi cổng phía bên trái trong `compose.yaml`, ví dụ `"8082:80"`. Nếu đổi cổng API, cập nhật `APP_URL` trong `.env`.

**Laravel không nhận cấu hình `.env` mới**

```powershell
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
```
