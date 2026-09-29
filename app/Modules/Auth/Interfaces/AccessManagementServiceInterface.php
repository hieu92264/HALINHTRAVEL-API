<?php

namespace App\Modules\Auth\Interfaces;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AccessManagementServiceInterface
{
    public function users(int $perPage = 15): LengthAwarePaginator;

    /** @return array<string, mixed> */
    public function user(User $user): array;

    /** @param array<string, mixed> $attributes */
    public function createUser(array $attributes): array;

    /** @param array<string, mixed> $attributes */
    public function updateUser(User $actor, User $user, array $attributes): array;

    public function deactivateUser(User $actor, User $user): void;

    /** @param list<int> $roleIds */
    public function syncUserRoles(User $actor, User $user, array $roleIds): array;

    /** @param list<int> $permissionIds */
    public function syncUserPermissions(User $user, array $permissionIds): array;

    public function roles(int $perPage = 15): LengthAwarePaginator;

    /** @return array<string, mixed> */
    public function role(Role $role): array;

    /** @param array<string, mixed> $attributes */
    public function createRole(array $attributes): array;

    /** @param array<string, mixed> $attributes */
    public function updateRole(Role $role, array $attributes): array;

    public function deleteRole(Role $role): void;

    /** @param list<int> $permissionIds */
    public function syncRolePermissions(Role $role, array $permissionIds): array;

    public function permissions(int $perPage = 15): LengthAwarePaginator;

    /** @return array<string, mixed> */
    public function permission(Permission $permission): array;

    /** @param array<string, mixed> $attributes */
    public function createPermission(array $attributes): array;

    /** @param array<string, mixed> $attributes */
    public function updatePermission(Permission $permission, array $attributes): array;

    public function deletePermission(Permission $permission): void;
}
