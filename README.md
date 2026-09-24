# Ha Linh Travel API

Laravel 12 API chạy trong Docker với Nginx, PHP-FPM 8.2, MySQL 8.4 và Redis.

## Yêu cầu

- [Git](https://git-scm.com/downloads)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) đang chạy (bật **Linux containers**)

Không cần cài PHP, Composer, MySQL, Redis hoặc Node.js trên máy.

## Cài đặt và chạy dự án

Mở PowerShell hoặc Terminal và thực hiện lần lượt:

```powershell
git clone <GIT_REPOSITORY_URL>
cd halinhtravel-api
Copy-Item .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Thay `<GIT_REPOSITORY_URL>` bằng URL repository thực tế. Nếu tên thư mục sau khi clone khác `halinhtravel-api`, dùng tên thư mục đó ở lệnh `cd`.

Sau khi hoàn tất, các dịch vụ có tại:

| Dịch vụ | Địa chỉ |
| --- | --- |
| API Laravel | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| MySQL từ máy host | `127.0.0.1:3307` |
| Redis từ máy host | `127.0.0.1:6380` |

Đăng nhập phpMyAdmin bằng user `root` và mật khẩu `DB_ROOT_PASSWORD` trong file `.env` (mặc định là `root`).

## Cấu hình môi trường

File `.env` không được commit vào Git. File này chứa cấu hình chạy local, gồm cả các giá trị mà Docker Compose dùng để khởi tạo MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=halinhtravel_db
DB_USERNAME=halinhtravel_user
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root

REDIS_HOST=redis
REDIS_PORT=6379
```

`db` và `redis` là tên service Docker, vì vậy ứng dụng kết nối nội bộ qua cổng `3306` và `6379`, không dùng các cổng đã publish ra máy host.

> Đổi mật khẩu MySQL chỉ có hiệu lực khi database được tạo lần đầu. Xem phần “Làm mới database” nếu bạn đã chạy dự án trước đó.

## Frontend assets (nếu có thay đổi Vite)

```powershell
docker compose exec app npm install
docker compose exec app npm run build
```

Để chạy Vite development server:

```powershell
docker compose exec app npm run dev
```

## Các lệnh thường dùng

```powershell
# Xem trạng thái và log
docker compose ps
docker compose logs -f

# Chạy lệnh Artisan
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate

# Dừng containers, vẫn giữ database và Redis
docker compose down

# Khởi động lại
docker compose up -d
```

## Làm mới database local

Lệnh dưới đây xóa toàn bộ dữ liệu MySQL và Redis của dự án rồi khởi tạo lại. Chỉ dùng cho môi trường local.

```powershell
docker compose down -v
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan migrate
```

## Khắc phục sự cố

**Cổng 8080, 8081, 3307 hoặc 6380 đã được sử dụng**

Đổi phần bên trái trong `ports` tại `compose.yaml`. Ví dụ `"8082:80"` sẽ mở API tại `http://localhost:8082`; đồng thời cập nhật `APP_URL` trong `.env`.

**Không kết nối được Docker**

Mở Docker Desktop, chờ trạng thái Engine đang chạy, rồi chạy lại `docker compose up -d --build`.

**Laravel báo lỗi database hoặc Redis sau khi đổi `.env`**

```powershell
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
```

Nếu bạn đổi thông tin MySQL sau lần khởi tạo đầu tiên, hãy làm mới database theo phần phía trên.

## Lưu ý triển khai production

Cấu hình này phục vụ development local. Trước khi deploy, cần dùng secret thật, tắt `APP_DEBUG`, không public trực tiếp MySQL/Redis/phpMyAdmin và bổ sung HTTPS, queue worker cùng scheduler.
