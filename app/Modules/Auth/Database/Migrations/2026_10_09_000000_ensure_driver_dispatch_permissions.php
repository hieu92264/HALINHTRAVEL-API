<?php

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use App\Shared\Enums\PermissionEnum;
use App\Shared\Enums\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Restore the driver dispatch permissions for databases seeded before the
     * driver workspace permissions were added to the permission catalog.
     */
    public function up(): void
    {
        $permissions = collect([
            PermissionEnum::MY_DISPATCH_ORDERS_VIEW,
            PermissionEnum::MY_DISPATCH_ORDERS_MANAGE,
        ])->map(function (PermissionEnum $permission): Permission {
            $model = Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'api',
            ]);

            if (! $model->is_active) {
                $model->forceFill(['is_active' => true])->save();
            }

            return $model;
        });

        $driver = Role::query()
            ->where('name', RoleEnum::DRIVER->value)
            ->where('guard_name', 'api')
            ->first();

        if ($driver !== null) {
            if (! $driver->is_active) {
                $driver->forceFill(['is_active' => true])->save();
            }

            $driver->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Keep assigned permissions intact when rolling back to avoid removing
     * permissions that may have been used after this migration ran.
     */
    public function down(): void
    {
        // Intentionally irreversible.
    }
};
