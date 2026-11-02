<?php

namespace App\Modules\Other\Interfaces;

interface OperationalReportServiceInterface
{
    public function workerVehicles(array $filters): array;

    public function touristVehicles(array $filters): array;

    public function profit(array $filters): array;

    public function payrollSources(array $filters): array;
}
