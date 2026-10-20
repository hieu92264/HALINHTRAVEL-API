<?php

namespace App\Modules\Other\Interfaces;

interface ReportServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function fixedTrips(array $filters): array;

    /** @param array<string, mixed> $filters */
    public function tourismTrips(array $filters): array;

    /** @param array<string, mixed> $filters */
    public function cashflow(array $filters): array;

    /** @param array<string, mixed> $filters */
    public function expenses(array $filters): array;

    /** @param array<string, mixed> $filters */
    public function payroll(array $filters): array;

    /** @param array<string, mixed> $filters */
    public function profitLoss(array $filters): array;

    /** @param array<string, mixed> $filters */
    public function vehicleProfit(array $filters): array;

    /** @param array<string, mixed> $filters */
    public function payrollDetails(array $filters): array;
}
