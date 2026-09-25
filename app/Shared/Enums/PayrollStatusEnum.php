<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum PayrollStatusEnum: string
{
    use EnumToArray;

    case DRAFT = 'draft';
    case CALCULATED = 'calculated';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case LOCKED = 'locked';
}
