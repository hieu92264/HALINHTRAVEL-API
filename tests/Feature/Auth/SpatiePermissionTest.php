<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Enums\Permission;
use App\Modules\Auth\Enums\Role;
use App\Modules\Auth\Models\Permission as PermissionModel;
use App\Modules\Auth\Models\Role as RoleModel;
use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpatiePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_role_grants_its_seeded_permissions(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $role = RoleModel::findByName(Role::Staff->value, 'api');
        $permission = PermissionModel::findByName(Permission::UsersView->value, 'api');
        $user = User::factory()->create();
        $user->assignRole(Role::Staff->value);

        $this->assertTrue($role->is_active);
        $this->assertTrue($permission->is_active);
        $this->assertTrue($user->hasRole(Role::Staff->value));
        $this->assertTrue($user->can(Permission::UsersView->value));
        $this->assertFalse($user->can(Permission::UsersManage->value));
    }
}
