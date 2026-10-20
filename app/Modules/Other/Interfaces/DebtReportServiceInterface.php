<?php

namespace App\Modules\Other\Interfaces;

interface DebtReportServiceInterface
{
    public function customerDebts(?string $fromDate, ?string $toDate): array;

    public function partnerDebts(?string $fromDate, ?string $toDate): array;

    public function externalDriverDebts(?string $fromDate, ?string $toDate): array;

    public function customerDebtDetails(int $customerId, string $fromDate, string $toDate): array;

    public function partnerDebtDetails(int $partnerId, string $fromDate, string $toDate): array;
}
