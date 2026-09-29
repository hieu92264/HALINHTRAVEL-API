<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Interfaces\AccessManagementServiceInterface;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use App\Shared\Enums\PermissionEnum;
use App\Shared\Enums\RoleEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_routes_require_authentication_and_permission(): void
    {
        $this->getJson('/api/auth/users')->assertUnauthorized();

        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->actingAs($driver, 'api')
            ->getJson('/api/auth/users')
            ->assertForbidden();
    }

    public function test_admin_can_create_user_and_sync_roles_and_permissions(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $admin = User::query()->where('user_name', 'admin')->firstOrFail();
        $sales = Role::findByName(RoleEnum::SALES->value, 'api');
        $permission = Permission::findByName(PermissionEnum::ATTACHMENTS_VIEW->value, 'api');

        $response = $this->actingAs($admin, 'api')->postJson('/api/auth/users', [
            'user_name' => 'custom-user',
            'email' => 'custom-user@example.test',
            'password' => 'secure-password',
            'role_ids' => [$sales->id],
        ])->assertCreated();

        $userId = $response->json('metadata.id');

        $this->actingAs($admin, 'api')->putJson("/api/auth/users/{$userId}/permissions", [
            'permission_ids' => [$permission->id],
        ])->assertOk()
            ->assertJsonPath('metadata.direct_permissions.0', PermissionEnum::ATTACHMENTS_VIEW->value);

        $user = User::findOrFail($userId);
        $this->assertTrue($user->hasRole(RoleEnum::SALES->value));
        $this->assertTrue($user->hasDirectPermission(PermissionEnum::ATTACHMENTS_VIEW->value));
    }

    public function test_custom_roles_and_permissions_can_be_managed_but_system_catalog_cannot(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $admin = User::query()->where('user_name', 'admin')->firstOrFail();

        $permissionId = $this->actingAs($admin, 'api')->postJson('/api/auth/permissions', [
            'name' => 'reports.export',
        ])->assertCreated()->json('metadata.id');

        $roleId = $this->actingAs($admin, 'api')->postJson('/api/auth/roles', [
            'name' => 'reporter',
        ])->assertCreated()->json('metadata.id');

        $this->actingAs($admin, 'api')->putJson("/api/auth/roles/{$roleId}/permissions", [
            'permission_ids' => [$permissionId],
        ])->assertOk()
            ->assertJsonPath('metadata.permissions.0', 'reports.export');

        $systemRole = Role::findByName(RoleEnum::ADMIN->value, 'api');
        $systemPermission = Permission::findByName(PermissionEnum::USERS_VIEW->value, 'api');

        $this->actingAs($admin, 'api')->putJson("/api/auth/roles/{$systemRole->id}", [
            'name' => 'renamed-admin',
        ])->assertForbidden();
        $this->actingAs($admin, 'api')->deleteJson("/api/auth/permissions/{$systemPermission->id}")->assertForbidden();

        $this->actingAs($admin, 'api')->deleteJson("/api/auth/roles/{$roleId}")->assertOk();
        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
    }

    public function test_last_active_admin_cannot_be_deactivated(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $admin = User::query()->where('user_name', 'admin')->firstOrFail();
        $actor = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(AccessManagementServiceInterface::class)->deactivateUser($actor, $admin);
    }

    public function test_user_is_soft_deleted_restored_and_cannot_have_its_username_changed(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $admin = User::query()->where('user_name', 'admin')->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($admin, 'api')->deleteJson("/api/auth/users/{$user->id}")->assertOk();
        $this->assertFalse((bool) $user->fresh()->is_active);

        $this->actingAs($admin, 'api')->putJson("/api/auth/users/{$user->id}", [
            'is_active' => true,
            'user_name' => 'new-name-is-ignored',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertNotSame('new-name-is-ignored', $user->user_name);
    }

    public function test_deleting_custom_permission_removes_direct_user_assignment(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $admin = User::query()->where('user_name', 'admin')->firstOrFail();
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'reports.download', 'guard_name' => 'api']);
        $user->givePermissionTo($permission);

        $this->actingAs($admin, 'api')->deleteJson("/api/auth/permissions/{$permission->id}")->assertOk();

        $this->assertDatabaseMissing('model_has_permissions', [
            'permission_id' => $permission->id,
            'model_id' => $user->id,
            'model_type' => User::class,
        ]);
    }
}
