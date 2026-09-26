<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\Permission as PermissionModel;
use App\Modules\Auth\Models\Role as RoleModel;
use App\Modules\Auth\Models\User;
use App\Shared\Enums\PermissionEnum;
use App\Shared\Enums\RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpatiePermissionTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_driver_has_no_global_trip_permissions(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::DRIVER->value);

        $this->assertFalse($user->can(PermissionEnum::TRIP_SCHEDULES_VIEW->value));
        $this->assertFalse($user->can(PermissionEnum::TRIP_SCHEDULES_MANAGE->value));
    }

    public function test_seeder_creates_an_account_for_every_role(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        foreach (RoleEnum::cases() as $role) {
            $user = User::query()->where('user_name', $role->value)->firstOrFail();

            $this->assertSame($role->value.'@halinhtravel.test', $user->email);
            $this->assertTrue($user->hasRole($role->value));
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $user->password));
        }
    }

    public function test_user_password_is_hashed_and_used_by_laravel_authentication(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->assertNotSame('secret-password', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret-password', $user->getAuthPassword()));
    }
}
