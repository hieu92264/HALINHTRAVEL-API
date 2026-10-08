<?php

namespace App\Modules\Dispatch\Interfaces;

use App\Modules\Dispatch\DTOs\TripAssignmentData;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\Auth\Models\User;

interface TripAssignmentServiceInterface
{
    public function getList(TripSchedule $schedule): array;
    public function assign(TripSchedule $schedule, TripAssignmentData $data, User $user): array;
    public function substitute(TripSchedule $schedule, TripAssignmentData $data, User $user): array;
    public function removeCurrent(TripAssignment $assignment): array;
}
