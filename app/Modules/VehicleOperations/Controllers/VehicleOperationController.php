<?php

namespace App\Modules\VehicleOperations\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Modules\VehicleOperations\Services\VehicleOperationService;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleOperationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VehicleOperationService $service) {}

    public function workerIndex(Request $request): JsonResponse
    {
        return $this->success($this->service->runs('worker', $request->only(['from_date', 'to_date', 'status', 'customer_id', 'route_id', 'driver_id', 'license_plate'])));
    }

    public function workerShow(WorkerVehicle $workerVehicle): JsonResponse
    {
        return $this->success($this->service->workerRun($workerVehicle));
    }

    public function workerStore(Request $request): JsonResponse
    {
        return $this->success($this->service->createRun('worker', $request->all()), 'Tạo xe chạy công nhân thành công.', Response::HTTP_CREATED);
    }

    public function workerUpdate(Request $request, WorkerVehicle $workerVehicle): JsonResponse
    {
        return $this->success($this->service->updateRun('worker', $workerVehicle, $request->all()));
    }

    public function workerDestroy(WorkerVehicle $workerVehicle): JsonResponse
    {
        $this->service->deleteRun($workerVehicle);

        return $this->success(null, 'Đã hủy xe chạy.');
    }

    public function workerStart(Request $request, WorkerVehicle $workerVehicle): JsonResponse
    {
        return $this->success($this->service->startWorkerRun($workerVehicle, $request->validate(['actual_started_at' => ['nullable', 'date']])['actual_started_at'] ?? null));
    }

    public function workerComplete(Request $request, WorkerVehicle $workerVehicle): JsonResponse
    {
        return $this->success($this->service->completeWorkerRun($workerVehicle, $request->validate(['actual_completed_at' => ['nullable', 'date']])['actual_completed_at'] ?? null), 'Đã hoàn thành xe chạy công nhân.');
    }

    public function touristIndex(Request $request): JsonResponse
    {
        return $this->success($this->service->runs('tourist', $request->only(['from_date', 'to_date', 'status', 'customer_id', 'driver_id', 'license_plate'])));
    }

    public function touristShow(TouristVehicle $touristVehicle): JsonResponse
    {
        return $this->success($this->service->touristRun($touristVehicle));
    }

    public function touristStore(Request $request): JsonResponse
    {
        return $this->success($this->service->createRun('tourist', $request->all()), 'Tạo xe chạy du lịch thành công.', Response::HTTP_CREATED);
    }

    public function touristUpdate(Request $request, TouristVehicle $touristVehicle): JsonResponse
    {
        return $this->success($this->service->updateRun('tourist', $touristVehicle, $request->all()));
    }

    public function touristDestroy(TouristVehicle $touristVehicle): JsonResponse
    {
        $this->service->deleteRun($touristVehicle);

        return $this->success(null, 'Đã hủy xe chạy.');
    }

    public function touristStart(Request $request, TouristVehicle $touristVehicle): JsonResponse
    {
        return $this->success($this->service->startTouristRun($touristVehicle, $request->validate(['actual_started_at' => ['nullable', 'date']])['actual_started_at'] ?? null));
    }

    public function touristComplete(Request $request, TouristVehicle $touristVehicle): JsonResponse
    {
        return $this->success($this->service->completeTouristRun($touristVehicle, $request->validate(['actual_completed_at' => ['nullable', 'date']])['actual_completed_at'] ?? null), 'Đã hoàn thành xe chạy du lịch.');
    }

}
