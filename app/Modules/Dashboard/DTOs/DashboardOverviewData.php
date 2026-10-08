<?php

namespace App\Modules\Dashboard\DTOs;

readonly class DashboardOverviewData
{
    public function __construct(public ?string $date) {}
}
