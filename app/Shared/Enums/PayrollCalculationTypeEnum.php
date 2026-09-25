<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum PayrollCalculationTypeEnum: string
{
    use EnumToArray;

    case FIXED_TRIP = 'fixed_trip';
    case TOURISM_COMMISSION = 'tourism_commission';
    case ALLOWANCE = 'allowance';
}
