<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum RentalServiceTypeEnum: string
{
    use EnumToArray;

    case FIXED = 'fixed';
    case TOURISM = 'tourism';
    case SCHOOL = 'school';
    case BUSINESS = 'business';
}
