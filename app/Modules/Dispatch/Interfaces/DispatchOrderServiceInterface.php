<?php

namespace App\Modules\Dispatch\Interfaces;

use App\Modules\Auth\Models\User;
use App\Modules\Dispatch\DTOs\CompletionConfirmationData;
use App\Modules\Dispatch\DTOs\CompletionReportData;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Dispatch\Models\TripSchedule;

interface DispatchOrderServiceInterface
{
    public function getList(): array;
    public function getDetail(DispatchOrder $order): array;
    public function issue(TripSchedule $schedule, User $user): array;
    public function assign(DispatchOrder $order): array;
    public function start(DispatchOrder $order, User $user, bool $driverOnly = false): array;
    public function reportCompletion(DispatchOrder $order, CompletionReportData $data, User $user, bool $driverOnly = false): array;
    public function confirmCompletion(DispatchOrder $order, CompletionConfirmationData $data, User $user): array;
    public function returnCompletion(DispatchOrder $order, string $note): array;
    public function cancel(DispatchOrder $order): array;
    public function getMyOrders(User $user): array;
    public function getMyOrder(DispatchOrder $order, User $user): array;
}
