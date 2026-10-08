<?php

namespace App\Modules\Rental\Commands;

use App\Modules\Rental\Interfaces\QuotationServiceInterface;
use Illuminate\Console\Command;

class ExpireQuotations extends Command
{
    protected $signature = 'rental:expire-quotations';

    protected $description = 'Đánh dấu hết hạn các báo giá đang mở đã quá ngày hiệu lực';

    public function handle(QuotationServiceInterface $quotationService): int
    {
        $count = $quotationService->expireDue();
        $this->info("Đã đánh dấu hết hạn {$count} báo giá.");

        return self::SUCCESS;
    }
}
