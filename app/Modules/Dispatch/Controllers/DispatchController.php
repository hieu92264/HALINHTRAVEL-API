<?php

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Interfaces\DispatchServiceInterface;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\Dispatch\Requests\AssignmentRequest;
use App\Modules\Dispatch\Requests\CancelDispatchOrderRequest;
use App\Modules\Dispatch\Requests\CompleteDispatchOrderRequest;
use App\Modules\Dispatch\Requests\StartDispatchOrderRequest;
use App\Modules\Dispatch\Requests\StoreTripScheduleRequest;
use App\Modules\Dispatch\Requests\UpdateTripScheduleRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DispatchController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DispatchServiceInterface $service) {}

    public function schedules(): JsonResponse
    {
        return $this->success($this->service->schedules(), 'Lấy lịch chuyến thành công.');
    }

    public function schedule(TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->schedule($tripSchedule), 'Lấy lịch chuyến thành công.');
    }

    public function storeSchedule(StoreTripScheduleRequest $request): JsonResponse
    {
        return $this->success($this->service->createSchedule($request->validated()), 'Tạo lịch chuyến thành công.', Response::HTTP_CREATED);
    }

    public function updateSchedule(UpdateTripScheduleRequest $request, TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->updateSchedule($tripSchedule, $request->validated()), 'Cập nhật lịch chuyến thành công.');
    }

    public function cancelSchedule(Request $request, TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->cancelSchedule($tripSchedule, $request->string('note')->toString() ?: null), 'Đã hủy lịch chuyến.');
    }

    public function assignments(TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->assignments($tripSchedule), 'Lấy phân công thành công.');
    }

    public function assign(AssignmentRequest $request, TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->assign($tripSchedule, $request->validated(), $request->user('api')), 'Đã phân công lịch chuyến.', Response::HTTP_CREATED);
    }

    public function substitute(AssignmentRequest $request, TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->substitute($tripSchedule, $request->validated(), $request->user('api')), 'Đã thay thế phân công.', Response::HTTP_CREATED);
    }

    public function removeAssignment(int $assignment): JsonResponse
    {
        $this->service->removeAssignment($assignment);

        return $this->success(null, 'Đã bỏ phân công hiện hành.');
    }

    public function orders(): JsonResponse
    {
        return $this->success($this->service->orders(), 'Lấy lệnh điều xe thành công.');
    }

    public function order(DispatchOrder $dispatchOrder): JsonResponse
    {
        return $this->success($this->service->order($dispatchOrder), 'Lấy lệnh điều xe thành công.');
    }

    public function createOrder(Request $request, TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->createOrder($tripSchedule, $request->user('api')), 'Tạo lệnh điều xe thành công.', Response::HTTP_CREATED);
    }

    public function assignOrder(DispatchOrder $dispatchOrder): JsonResponse
    {
        return $this->success($this->service->assignOrder($dispatchOrder), 'Đã xác nhận lệnh điều xe.');
    }

    public function start(StartDispatchOrderRequest $request, DispatchOrder $dispatchOrder): JsonResponse
    {
        return $this->success($this->service->start($dispatchOrder, $request->validated(), $request->user('api')), 'Đã bắt đầu chuyến.');
    }

    public function complete(CompleteDispatchOrderRequest $request, DispatchOrder $dispatchOrder): JsonResponse
    {
        return $this->success($this->service->complete($dispatchOrder, $request->validated(), $request->user('api')), 'Đã hoàn thành chuyến.');
    }

    public function cancelOrder(CancelDispatchOrderRequest $request, DispatchOrder $dispatchOrder): JsonResponse
    {
        return $this->success($this->service->cancelOrder($dispatchOrder, $request->validated('note')), 'Đã hủy lệnh điều xe.');
    }

    public function myOrders(Request $request): JsonResponse
    {
        return $this->success($this->service->myOrders($request->user('api')), 'Lấy lệnh của tôi thành công.');
    }

    public function myOrder(Request $request, DispatchOrder $dispatchOrder): JsonResponse
    {
        return $this->success($this->service->myOrder($dispatchOrder, $request->user('api')), 'Lấy lệnh của tôi thành công.');
    }
}
