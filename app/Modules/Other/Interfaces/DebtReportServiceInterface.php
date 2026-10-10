<?php

namespace App\Modules\Other\Interfaces;

interface DebtReportServiceInterface
{
    public function customerDebts(?string $fromDate, ?string $toDate): array;

    public function partnerDebts(?string $fromDate, ?string $toDate): array;
}
