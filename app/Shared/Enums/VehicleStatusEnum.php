<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum VehicleStatusEnum: string
{
    use EnumToArray;

    case AVAILABLE = 'available';
    case ASSIGNED = 'assigned';
    case MAINTENANCE = 'maintenance';
    case INACTIVE = 'inactive';
    case OTHER = 'other';
}
