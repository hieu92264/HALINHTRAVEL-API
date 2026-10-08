<?php

namespace App\Modules\Dashboard\Interfaces;

use App\Modules\Auth\Models\User;
use App\Modules\Dashboard\DTOs\DashboardOverviewData;

interface DashboardOverviewServiceInterface
{
    /** @return array<string, mixed> */
    public function overview(User $user, DashboardOverviewData $data): array;
}
