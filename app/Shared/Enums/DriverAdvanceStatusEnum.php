<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum DriverAdvanceStatusEnum: string
{
    use EnumToArray;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PAYROLL_LOCKED = 'payroll_locked';

    // Legacy states are retained for audit only. New workflows never create them.
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
