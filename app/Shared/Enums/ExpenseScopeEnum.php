<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum ExpenseScopeEnum: string
{
    use EnumToArray;

    case VEHICLE = 'vehicle';
    case TRIP = 'trip';
    case GENERAL = 'general';
}
