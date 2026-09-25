<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum QuotationStatusEnum: string
{
    use EnumToArray;

    case DRAFT = 'draft';
    case SENT = 'sent';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
}
