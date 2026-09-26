# Authorization với Spatie Permission

Project đã dùng `spatie/laravel-permission` và guard mặc định là `api` (JWT). `User` dùng trait `HasRoles`, vì vậy role và permission phải được tạo với guard `api`.

`Role` và `Permission` cũng dùng metadata giống `User`: `is_active`, `user_name_created`, `user_name_updated`. Các model mở rộng nằm tại `App\Modules\Auth\Models\Role` và `App\Modules\Auth\Models\Permission`; Spatie đã được cấu hình để luôn dùng chúng.

## Khởi tạo dữ liệu quyền

Migration của package đã nằm trong module Auth. Vì migration này tạo thêm metadata cho bảng `roles` và `permissions`, với database local hiện có hãy tạo lại database:

```powershell
php artisan db:wipe
php artisan migrate --seed
```

Seeder cũng tạo sáu tài khoản ban đầu: `admin`, `director`, `sales`, `dispatcher`, `accountant` và `driver`. Email tương ứng là `<username>@halinhtravel.test`; mật khẩu lấy từ `AUTH_SEED_PASSWORD` (mặc định local: `password`). Đặt biến này trong `.env` trước khi seed môi trường không phải local.

Seeder tạo catalog permission theo resource; role `admin` có toàn bộ quyền. Tên permission tập trung tại `App\Shared\Enums\PermissionEnum`; hãy thêm case mới tại đây và chạy lại `php artisan db:seed`.

## Gán role hoặc quyền cho người dùng

Ví dụ trong Tinker, seeder, service hoặc action:

```php
use App\Shared\Enums\PermissionEnum;
use App\Shared\Enums\RoleEnum;
use App\Modules\Auth\Models\User;

$user = User::findOrFail(1);
$user->assignRole(RoleEnum::SALES->value);
$user->givePermissionTo(PermissionEnum::USERS_MANAGE->value); // quyền trực tiếp, nếu cần

// Thay thế role hiện có:
$user->syncRoles([RoleEnum::ADMIN->value]);
```

Không cần nhúng permission vào JWT: middleware lấy user từ token rồi kiểm tra quyền trong database/cache của Spatie.

## Bảo vệ route API

Các alias `role`, `permission` và `role_or_permission` đã được đăng ký trong `bootstrap/app.php`. Luôn đặt `auth:api` trước middleware kiểm tra quyền.

```php
use App\Shared\Enums\PermissionEnum;
use App\Shared\Enums\RoleEnum;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'permission:'.PermissionEnum::USERS_VIEW->value])
    ->get('/users', ListUsersController::class);

Route::middleware(['auth:api', 'role:'.RoleEnum::ADMIN->value])
    ->delete('/users/{user}', DeleteUserController::class);

Route::middleware(['auth:api', 'role_or_permission:admin|'.PermissionEnum::ROLES_MANAGE->value])
    ->put('/roles/{role}', UpdateRoleController::class);
```

Middleware trả về HTTP 403 khi user đã xác thực nhưng không có quyền; định dạng lỗi API thống nhất đã được xử lý trong `bootstrap/app.php`.

## Kiểm tra trong controller/service

```php
use App\Shared\Enums\PermissionEnum;

public function update(Request $request, User $user): JsonResponse
{
    $this->authorize(PermissionEnum::USERS_MANAGE->value);

    // Hoặc: abort_unless($request->user()->can(PermissionEnum::USERS_MANAGE->value), 403);
    // ...
}
```

Spatie đăng ký permission với Laravel Gate, nên `$user->can(...)`, `$this->authorize(...)` và middleware `permission` đều kiểm tra cùng một permission.

## Lưu ý vận hành

- Cần xóa cache cấu hình sau khi đổi `config/permission.php`: `php artisan optimize:clear`.
- Khi thêm/xóa quyền bằng API, dùng các method Spatie (`givePermissionTo`, `syncPermissions`, `assignRole`, `syncRoles`) thay vì ghi trực tiếp vào bảng pivot; package sẽ tự làm mới cache quyền.
- Nếu dùng guard khác ngoài `api`, role và permission phải được tạo cùng `guard_name` với guard đó.
