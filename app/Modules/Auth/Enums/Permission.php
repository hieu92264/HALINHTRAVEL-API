<?php

namespace App\Modules\Auth\Enums;

/**
 * Permission names used by the authorization layer.
 *
 * Add permissions here first, then run `php artisan db:seed` to register them.
 */
enum Permission: string
{
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $permission): string => $permission->value, self::cases());
    }
}
