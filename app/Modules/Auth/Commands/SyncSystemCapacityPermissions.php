<?php

namespace App\Modules\Auth\Commands;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use App\Shared\Enums\PermissionEnum;
use App\Shared\Enums\RoleEnum;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class SyncSystemCapacityPermissions extends Command
{
    protected $signature = 'auth:sync-system-capacity-permissions';

    protected $description = 'Khôi phục quyền kiểm tra năng lực cho các vai trò hệ thống cần dùng';

    public function handle(): int
    {
        $permission = Permission::findByName(PermissionEnum::RENTAL_CAPACITY_VIEW->value, 'api');
        $roleNames = [RoleEnum::ADMIN->value, RoleEnum::SALES->value, RoleEnum::DISPATCHER->value];
        $updated = 0;

        foreach (Role::query()->where('guard_name', 'api')->whereIn('name', $roleNames)->get() as $role) {
            if ($role->hasPermissionTo($permission)) {
                continue;
            }

            $role->givePermissionTo($permission);
            $updated++;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info("Đã khôi phục quyền kiểm tra năng lực cho {$updated} vai trò.");

        return self::SUCCESS;
    }
}
