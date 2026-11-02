<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Models\Permission as PermissionModel;
use App\Modules\Auth\Models\Role as RoleModel;
use App\Modules\Auth\Models\User;
use App\Shared\Enums\PermissionEnum;
use App\Shared\Enums\RoleEnum;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SpatiePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_assigns_admin_the_users_view_permission(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = RoleModel::findByName(RoleEnum::ADMIN->value, 'api');

        $this->assertTrue($admin->hasPermissionTo(PermissionEnum::USERS_VIEW->value));
    }

    public function test_a_role_grants_its_seeded_permissions(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $role = RoleModel::findByName(RoleEnum::SALES->value, 'api');
        $permission = PermissionModel::findByName(PermissionEnum::CUSTOMERS_VIEW->value, 'api');
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SALES->value);

        $this->assertTrue($role->is_active);
        $this->assertTrue($permission->is_active);
        $this->assertTrue($user->hasRole(RoleEnum::SALES->value));
        $this->assertTrue($user->can(PermissionEnum::CUSTOMERS_VIEW->value));
        $this->assertTrue($user->can(PermissionEnum::CUSTOMERS_MANAGE->value));
        $this->assertFalse($user->can(PermissionEnum::PAYROLLS_MANAGE->value));
    }

    public function test_seeder_creates_users_view_permission_for_the_api_guard(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $permission = PermissionModel::findByName(PermissionEnum::USERS_VIEW->value, 'api');
        $admin = RoleModel::findByName(RoleEnum::ADMIN->value, 'api');

        $this->assertSame('api', $permission->guard_name);
        $this->assertTrue($admin->hasPermissionTo($permission));
    }

    public function test_driver_has_no_global_vehicle_operation_permissions(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::DRIVER->value);

        $this->assertFalse($user->can(PermissionEnum::WORKER_VEHICLES_VIEW->value));
        $this->assertFalse($user->can(PermissionEnum::TOURIST_VEHICLES_VIEW->value));
    }

    public function test_auth_seeder_restores_the_current_permission_catalog_and_empty_driver_role(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $driver = RoleModel::findByName(RoleEnum::DRIVER->value, 'api');
        $workerView = PermissionModel::findByName(PermissionEnum::WORKER_VEHICLES_VIEW->value, 'api');

        $driver->syncPermissions([PermissionEnum::WORKER_VEHICLES_VIEW->value]);
        $workerView->forceFill(['is_active' => false])->save();

        $this->seed(AuthDatabaseSeeder::class);

        $driver->refresh();
        $workerView->refresh();

        $this->assertTrue($workerView->is_active);
        $this->assertFalse($driver->hasPermissionTo(PermissionEnum::WORKER_VEHICLES_VIEW->value));
    }

    public function test_default_roles_receive_vehicle_operation_permissions(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $admin = RoleModel::findByName(RoleEnum::ADMIN->value, 'api');
        $dispatcher = RoleModel::findByName(RoleEnum::DISPATCHER->value, 'api');
        $accountant = RoleModel::findByName(RoleEnum::ACCOUNTANT->value, 'api');

        $this->assertTrue($admin->hasPermissionTo(PermissionEnum::WORKER_VEHICLES_MANAGE->value));
        $this->assertTrue($admin->hasPermissionTo(PermissionEnum::TOURIST_VEHICLES_MANAGE->value));
        $this->assertTrue($dispatcher->hasPermissionTo(PermissionEnum::WORKER_VEHICLE_SCHEDULES_MANAGE->value));
        $this->assertTrue($dispatcher->hasPermissionTo(PermissionEnum::TOURIST_VEHICLES_MANAGE->value));
        $this->assertTrue($accountant->hasPermissionTo(PermissionEnum::WORKER_VEHICLES_VIEW->value));
        $this->assertTrue($accountant->hasPermissionTo(PermissionEnum::TOURIST_VEHICLES_VIEW->value));
        $this->assertFalse($accountant->hasPermissionTo(PermissionEnum::WORKER_VEHICLES_MANAGE->value));
    }

    public function test_seeder_creates_an_account_for_every_role(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        foreach (RoleEnum::cases() as $role) {
            $user = User::query()->where('user_name', $role->value)->firstOrFail();

            $this->assertSame($role->value.'@halinhtravel.vn', $user->email);
            $this->assertTrue($user->hasRole($role->value));
            $this->assertTrue(Hash::check('password', $user->password));
        }
    }

    public function test_user_password_is_hashed_and_used_by_laravel_authentication(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->assertNotSame('secret-password', $user->password);
        $this->assertTrue(Hash::check('secret-password', $user->getAuthPassword()));
    }

    public function test_an_active_seeded_user_can_log_in_with_jwt(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $result = app(AuthServiceInterface::class)->login([
            'user_name' => 'admin',
            'password' => 'password',
        ]);

        $this->assertNotEmpty($result['access_token']);
        $this->assertSame('bearer', $result['token_type']);
        $this->assertGreaterThan(0, $result['expires_in']);
        $this->assertNotNull(User::query()->where('user_name', 'admin')->value('last_login_at'));
    }

    public function test_login_route_uses_the_registered_login_rate_limiter(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $this->postJson('/api/auth/login', [
            'user_name' => 'admin',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('metadata.token_type', 'bearer');
    }

    public function test_authenticated_user_can_change_password_only_with_current_password(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $token = app(AuthServiceInterface::class)->login(['user_name' => 'admin', 'password' => 'password'])['access_token'];

        $this->withToken($token)->putJson('/api/auth/password', [
            'current_password' => 'wrong-password',
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ])->assertUnprocessable();

        $this->withToken($token)->putJson('/api/auth/password', [
            'current_password' => 'password',
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ])->assertOk();

        $admin = User::query()->where('user_name', 'admin')->sole();
        $this->assertTrue(Hash::check('new-secure-password', $admin->password));
    }
}
