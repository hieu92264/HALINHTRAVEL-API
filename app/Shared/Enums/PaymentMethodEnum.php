<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum PaymentMethodEnum: string
{
    use EnumToArray;
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
}
