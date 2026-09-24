<?php

namespace App\Modules\Auth\Database\Seeds;

use App\Modules\Auth\Enums\Permission;
use App\Modules\Auth\Enums\Role;
use App\Modules\Auth\Models\Permission as PermissionModel;
use App\Modules\Auth\Models\Role as RoleModel;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class AuthDatabaseSeeder extends Seeder
{
    /**
     * Seed the Auth module's database records.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'api');
        }

        $admin = RoleModel::findOrCreate(Role::Admin->value, 'api');
        $admin->syncPermissions(Permission::values());

        $staff = RoleModel::findOrCreate(Role::Staff->value, 'api');
        $staff->syncPermissions([
            Permission::UsersView->value,
        ]);
    }
}
