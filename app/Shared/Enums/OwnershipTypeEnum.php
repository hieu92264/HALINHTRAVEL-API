<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum OwnershipTypeEnum: string
{
    use EnumToArray;

    case COMPANY = 'company';
    case PARTNER = 'partner';
}
