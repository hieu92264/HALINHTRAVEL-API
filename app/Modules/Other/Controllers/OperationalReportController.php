<?php

namespace App\Modules\Other\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Other\Interfaces\OperationalReportServiceInterface;
use App\Modules\Other\Requests\OperationalReportRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class OperationalReportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OperationalReportServiceInterface $service) {}

    public function workerVehicles(OperationalReportRequest $request): JsonResponse
    {
        return $this->success($this->service->workerVehicles($request->validated()), 'Lấy báo cáo xe chạy công nhân thành công.');
    }

    public function touristVehicles(OperationalReportRequest $request): JsonResponse
    {
        return $this->success($this->service->touristVehicles($request->validated()), 'Lấy báo cáo xe chạy du lịch thành công.');
    }

    public function profit(OperationalReportRequest $request): JsonResponse
    {
        return $this->success($this->service->profit($request->validated()), 'Lấy báo cáo lợi nhuận thành công.');
    }

    public function payrollSources(OperationalReportRequest $request): JsonResponse
    {
        return $this->success($this->service->payrollSources($request->validated()), 'Lấy nguồn công lái xe thành công.');
    }
}
