<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum WorkTypeEnum: string
{
    use EnumToArray;

    case FIXED_TRIP = 'fixed_trip';
    case TOURISM_TRIP = 'tourism_trip';
    case OTHER = 'other';
}
