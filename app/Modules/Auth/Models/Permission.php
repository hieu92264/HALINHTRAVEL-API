<?php

namespace App\Modules\Auth\Models;

use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasBaseMetadata;

    protected $fillable = [
        'name',
        'guard_name',
        'is_active',
    ];
}
