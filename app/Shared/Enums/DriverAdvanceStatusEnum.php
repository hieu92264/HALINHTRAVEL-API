<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum DriverAdvanceStatusEnum: string
{
    use EnumToArray;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
