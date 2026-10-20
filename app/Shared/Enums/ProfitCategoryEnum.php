<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum ProfitCategoryEnum: string
{
    use EnumToArray;

    case FUEL = 'fuel';
    case REPAIR = 'repair';
    case INSURANCE_REGISTRATION = 'insurance_registration';
    case OTHER = 'other';
}
