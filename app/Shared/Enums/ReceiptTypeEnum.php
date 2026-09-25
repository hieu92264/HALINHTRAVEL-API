<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum ReceiptTypeEnum: string
{
    use EnumToArray;

    case DEPOSIT = 'deposit'; // tiền đặt cọc
    case CONTRACT_PAYMENT = 'contract_payment'; // thanh toán hợp đồng
    case OTHER = 'other'; // các loại phiếu thu khác
}
