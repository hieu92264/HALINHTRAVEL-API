<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum CustomerEnum: string
{
    use EnumToArray;

    case INDIVIDUAL = 'individual'; // cá nhân
    case COMPANY = 'company'; // công ty
}
