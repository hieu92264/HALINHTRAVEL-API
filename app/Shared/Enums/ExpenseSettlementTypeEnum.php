<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum ExpenseSettlementTypeEnum: string
{
    use EnumToArray;

    case STANDARD = 'standard';
    case DRIVER_REIMBURSEMENT = 'driver_reimbursement';
    case EXTERNAL_DRIVER_PAYMENT = 'external_driver_payment';
}
