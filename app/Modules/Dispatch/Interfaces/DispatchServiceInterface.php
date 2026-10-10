<?php

namespace App\Modules\Dispatch\Interfaces;

use App\Modules\Auth\Models\User;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Dispatch\Models\TripSchedule;

interface DispatchServiceInterface
{
    public function schedules(): array;

    public function schedule(TripSchedule $schedule): array;

    public function createSchedule(array $data): array;

    public function updateSchedule(TripSchedule $schedule, array $data): array;

    public function cancelSchedule(TripSchedule $schedule, ?string $note): array;

    public function assignments(TripSchedule $schedule): array;

    public function assign(TripSchedule $schedule, array $data, User $actor): array;

    public function substitute(TripSchedule $schedule, array $data, User $actor): array;

    public function removeAssignment(int $assignmentId): void;

    public function orders(): array;

    public function order(DispatchOrder $order): array;

    public function createOrder(TripSchedule $schedule, User $actor): array;

    public function assignOrder(DispatchOrder $order): array;

    public function start(DispatchOrder $order, array $data, User $actor): array;

    public function complete(DispatchOrder $order, array $data, User $actor): array;

    public function cancelOrder(DispatchOrder $order, string $note): array;

    public function myOrders(User $actor): array;

    public function myOrder(DispatchOrder $order, User $actor): array;
}
