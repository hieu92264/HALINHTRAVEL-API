<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum ContractStatusEnum: string
{
    use EnumToArray;

    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
