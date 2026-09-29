<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Interfaces\AccessManagementServiceInterface;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use App\Shared\Enums\PermissionEnum;
use App\Shared\Enums\RoleEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AccessManagementService implements AccessManagementServiceInterface
{
    public function users(int $perPage = 15): LengthAwarePaginator
    {
        $users = User::query()->with(['roles', 'permissions'])->orderBy('id')->paginate($perPage);
        $users->setCollection($users->getCollection()->map(fn (User $user): array => $this->user($user)));

        return $users;
    }

    public function user(User $user): array
    {
        return [
            'id' => $user->id,
            'user_name' => $user->user_name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at?->toISOString(),
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'roles' => $user->getRoleNames()->values()->all(),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }

    public function createUser(array $attributes): array
    {
        $roleIds = $attributes['role_ids'] ?? [];
        unset($attributes['role_ids']);

        return DB::transaction(function () use ($attributes, $roleIds): array {
            $user = User::create($attributes);
            $user->syncRoles($this->activeRoles($roleIds));

            return $this->user($user->fresh(['roles', 'permissions']));
        });
    }

    public function updateUser(User $actor, User $user, array $attributes): array
    {
        if (array_key_exists('is_active', $attributes) && ! $attributes['is_active']) {
            $this->ensureUserCanBeDeactivated($actor, $user);
        }

        $user->fill($attributes)->save();

        return $this->user($user->fresh(['roles', 'permissions']));
    }

    public function deactivateUser(User $actor, User $user): void
    {
        $this->ensureUserCanBeDeactivated($actor, $user);
        $user->forceFill(['is_active' => false])->save();
    }

    public function syncUserRoles(User $actor, User $user, array $roleIds): array
    {
        $roles = $this->activeRoles($roleIds);
        $newRoleNames = $roles->pluck('name')->all();

        if ($actor->is($user) && ! in_array(RoleEnum::ADMIN->value, $newRoleNames, true)) {
            throw new AuthorizationException('You cannot remove your own administrator role.');
        }

        if ($user->is_active && $user->hasRole(RoleEnum::ADMIN->value)
            && ! in_array(RoleEnum::ADMIN->value, $newRoleNames, true)
            && $this->activeAdminCount() <= 1) {
            throw new AuthorizationException('At least one active administrator is required.');
        }

        $user->syncRoles($roles);

        return $this->user($user->fresh(['roles', 'permissions']));
    }

    public function syncUserPermissions(User $user, array $permissionIds): array
    {
        $user->syncPermissions($this->activePermissions($permissionIds));

        return $this->user($user->fresh(['roles', 'permissions']));
    }

    public function roles(int $perPage = 15): LengthAwarePaginator
    {
        $roles = Role::query()->with('permissions')->orderBy('id')->paginate($perPage);
        $roles->setCollection($roles->getCollection()->map(fn (Role $role): array => $this->role($role)));

        return $roles;
    }

    public function role(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'is_active' => $role->is_active,
            'is_system' => $this->isSystemRole($role),
            'permissions' => $role->permissions->pluck('name')->values()->all(),
            'created_at' => $role->created_at?->toISOString(),
            'updated_at' => $role->updated_at?->toISOString(),
        ];
    }

    public function createRole(array $attributes): array
    {
        $role = Role::create(['name' => $attributes['name'], 'guard_name' => 'api']);

        return $this->role($role->fresh('permissions'));
    }

    public function updateRole(Role $role, array $attributes): array
    {
        $this->ensureCustomRole($role);
        $role->update(['name' => $attributes['name']]);

        return $this->role($role->fresh('permissions'));
    }

    public function deleteRole(Role $role): void
    {
        $this->ensureCustomRole($role);
        $role->delete();
    }

    public function syncRolePermissions(Role $role, array $permissionIds): array
    {
        $role->syncPermissions($this->activePermissions($permissionIds));

        return $this->role($role->fresh('permissions'));
    }

    public function permissions(int $perPage = 15): LengthAwarePaginator
    {
        $permissions = Permission::query()->orderBy('id')->paginate($perPage);
        $permissions->setCollection($permissions->getCollection()->map(fn (Permission $permission): array => $this->permission($permission)));

        return $permissions;
    }

    public function permission(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
            'is_active' => $permission->is_active,
            'is_system' => $this->isSystemPermission($permission),
            'created_at' => $permission->created_at?->toISOString(),
            'updated_at' => $permission->updated_at?->toISOString(),
        ];
    }

    public function createPermission(array $attributes): array
    {
        $permission = Permission::create(['name' => $attributes['name'], 'guard_name' => 'api']);

        return $this->permission($permission);
    }

    public function updatePermission(Permission $permission, array $attributes): array
    {
        $this->ensureCustomPermission($permission);
        $permission->update(['name' => $attributes['name']]);

        return $this->permission($permission->fresh());
    }

    public function deletePermission(Permission $permission): void
    {
        $this->ensureCustomPermission($permission);
        $permission->delete();
    }

    private function ensureUserCanBeDeactivated(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw new AuthorizationException('You cannot deactivate your own account.');
        }

        if ($user->is_active && $user->hasRole(RoleEnum::ADMIN->value) && $this->activeAdminCount() <= 1) {
            throw new AuthorizationException('At least one active administrator is required.');
        }
    }

    private function ensureCustomRole(Role $role): void
    {
        if ($this->isSystemRole($role)) {
            throw new AuthorizationException('System roles cannot be modified.');
        }
    }

    private function ensureCustomPermission(Permission $permission): void
    {
        if ($this->isSystemPermission($permission)) {
            throw new AuthorizationException('System permissions cannot be modified.');
        }
    }

    private function isSystemRole(Role $role): bool
    {
        return in_array($role->name, RoleEnum::values(), true);
    }

    private function isSystemPermission(Permission $permission): bool
    {
        return in_array($permission->name, PermissionEnum::values(), true);
    }

    private function activeAdminCount(): int
    {
        return User::query()->active()->role(RoleEnum::ADMIN->value)->count();
    }

    /** @param list<int> $roleIds */
    private function activeRoles(array $roleIds): \Illuminate\Support\Collection
    {
        return Role::query()->whereIn('id', $roleIds)->where('guard_name', 'api')->active()->get();
    }

    /** @param list<int> $permissionIds */
    private function activePermissions(array $permissionIds): \Illuminate\Support\Collection
    {
        return Permission::query()->whereIn('id', $permissionIds)->where('guard_name', 'api')->active()->get();
    }
}
