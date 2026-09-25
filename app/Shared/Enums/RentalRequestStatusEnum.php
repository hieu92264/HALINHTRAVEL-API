<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum RentalRequestStatusEnum: string
{
    use EnumToArray;

    case NEW = 'new';
    case QUOTED = 'quoted';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case CONVERTED = 'converted';
}
