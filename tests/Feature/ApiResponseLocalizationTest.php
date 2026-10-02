<?php

namespace Tests\Feature;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiResponseLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_are_always_vietnamese_regardless_of_requested_locale(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $admin = User::query()->where('user_name', 'admin')->firstOrFail();

        $this->actingAs($admin, 'api')
            ->withHeader('X-Locale', 'en')
            ->getJson('/api/auth/users')
            ->assertOk()
            ->assertHeader('Content-Language', 'vi')
            ->assertJsonPath('message', 'Lấy danh sách người dùng thành công.');
    }

    public function test_validation_and_standard_api_errors_are_vietnamese(): void
    {
        $this->postJson('/api/auth/login', [])->assertUnprocessable()
            ->assertHeader('Content-Language', 'vi')
            ->assertJsonPath('message', 'Dữ liệu gửi lên không hợp lệ.')
            ->assertJsonPath('metadata.user_name.0', 'Trường tên đăng nhập là bắt buộc.');

        $this->getJson('/api/auth/users')->assertUnauthorized()
            ->assertJsonPath('message', 'Bạn chưa được xác thực.');

        $this->getJson('/api/not-found')->assertNotFound()
            ->assertJsonPath('message', 'Không tìm thấy đường dẫn hoặc tài nguyên.');

        $this->getJson('/api/auth/login')->assertMethodNotAllowed()
            ->assertJsonPath('message', 'Phương thức gửi yêu cầu không được hỗ trợ.');
    }

    public function test_invalid_login_and_forbidden_responses_are_vietnamese(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->postJson('/api/auth/login', [
            'user_name' => 'admin',
            'password' => 'incorrect-password',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Bạn chưa được xác thực.');

        $this->actingAs($driver, 'api')
            ->getJson('/api/auth/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'Bạn không có quyền truy cập tài nguyên này.');
    }
}
