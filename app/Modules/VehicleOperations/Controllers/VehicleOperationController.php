<?php

namespace App\Modules\VehicleOperations\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicleRate;
use App\Modules\VehicleOperations\Models\WorkerVehicleScheduleRule;
use App\Modules\VehicleOperations\Services\VehicleOperationService;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleOperationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VehicleOperationService $service) {}

    public function workerIndex(): JsonResponse
    {
        return $this->success($this->service->runs('worker'));
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

    public function touristIndex(): JsonResponse
    {
        return $this->success($this->service->runs('tourist'));
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

    public function rateIndex(): JsonResponse
    {
        return $this->success($this->service->rates());
    }

    public function rateStore(Request $request): JsonResponse
    {
        return $this->success($this->service->saveRate($request->all()), 'Tạo bảng giá thành công.', Response::HTTP_CREATED);
    }

    public function rateUpdate(Request $request, WorkerVehicleRate $workerVehicleRate): JsonResponse
    {
        return $this->success($this->service->saveRate([...$workerVehicleRate->getAttributes(), ...$request->all()], $workerVehicleRate));
    }

    public function ruleIndex(): JsonResponse
    {
        return $this->success($this->service->rules());
    }

    public function ruleStore(Request $request): JsonResponse
    {
        return $this->success($this->service->saveRule($request->all()), 'Tạo quy tắc lịch thành công.', Response::HTTP_CREATED);
    }

    public function ruleUpdate(Request $request, WorkerVehicleScheduleRule $workerVehicleScheduleRule): JsonResponse
    {
        return $this->success($this->service->saveRule([...$workerVehicleScheduleRule->getAttributes(), ...$request->all()], $workerVehicleScheduleRule));
    }

    public function generate(Request $request, WorkerVehicleScheduleRule $workerVehicleScheduleRule): JsonResponse
    {
        $data = $request->validate(['from_date' => ['required', 'date'], 'to_date' => ['required', 'date', 'after_or_equal:from_date']]);

        return $this->success($this->service->generateRuns($workerVehicleScheduleRule, $data['from_date'], $data['to_date']));
    }
}
