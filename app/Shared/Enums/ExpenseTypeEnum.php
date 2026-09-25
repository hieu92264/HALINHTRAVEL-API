<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum ExpenseTypeEnum: string
{
    use EnumToArray;

    case VEHICLE = 'vehicle';
    case TRIP = 'trip';
    case GENERAL = 'general';
}
