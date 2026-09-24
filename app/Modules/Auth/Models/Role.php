<?php

namespace App\Modules\Auth\Models;

use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasBaseMetadata;
}
