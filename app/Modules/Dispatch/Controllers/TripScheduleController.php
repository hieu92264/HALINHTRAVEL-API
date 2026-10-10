<?php

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Interfaces\TripScheduleServiceInterface;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\Dispatch\Requests\CancelTripScheduleRequest;
use App\Modules\Dispatch\Requests\StoreTripScheduleRequest;
use App\Modules\Dispatch\Requests\UpdateTripScheduleRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TripScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TripScheduleServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->getList(), 'Lấy danh sách lịch chuyến thành công.');
    }

    public function show(TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->getDetail($tripSchedule), 'Lấy chi tiết lịch chuyến thành công.');
    }

    public function store(StoreTripScheduleRequest $request): JsonResponse
    {
        return $this->success($this->service->store($request->toDTO()), 'Tạo lịch chuyến thành công.', Response::HTTP_CREATED);
    }

    public function update(UpdateTripScheduleRequest $request, TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->update($tripSchedule, $request->toDTO()), 'Cập nhật lịch chuyến thành công.');
    }

    public function destroy(TripSchedule $tripSchedule): JsonResponse
    {
        $this->service->deactivate($tripSchedule);

        return $this->success(null, 'Ngừng lịch chuyến thành công.');
    }

    public function cancel(CancelTripScheduleRequest $request, TripSchedule $tripSchedule): JsonResponse
    {
        return $this->success($this->service->cancel($tripSchedule, $request->validated('note')), 'Hủy lịch chuyến thành công.');
    }
}
