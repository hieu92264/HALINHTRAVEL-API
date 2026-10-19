<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum PayrollCalculationTypeEnum: string
{
    use EnumToArray;

    case FIXED_TRIP = 'fixed_trip';
    case TOURISM_COMMISSION = 'tourism_commission';
    case DIRECT_CUSTOMER_COLLECTION = 'direct_customer_collection';
    case DRIVER_REIMBURSEMENT = 'driver_reimbursement';
    case ALLOWANCE = 'allowance';
}
