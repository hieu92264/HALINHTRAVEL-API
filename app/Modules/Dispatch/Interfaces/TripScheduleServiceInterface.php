<?php

namespace App\Modules\Dispatch\Interfaces;

use App\Modules\Dispatch\DTOs\TripScheduleData;
use App\Modules\Dispatch\Models\TripSchedule;

interface TripScheduleServiceInterface
{
    public function getList(): array;

    public function getDetail(TripSchedule $schedule): array;

    public function store(TripScheduleData $data): array;

    public function update(TripSchedule $schedule, TripScheduleData $data): array;

    public function deactivate(TripSchedule $schedule): void;

    public function cancel(TripSchedule $schedule, ?string $note = null): array;
}
