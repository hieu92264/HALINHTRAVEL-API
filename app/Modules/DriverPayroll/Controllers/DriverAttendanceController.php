<?php

namespace App\Modules\DriverPayroll\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DriverPayroll\Interfaces\DriverPayrollServiceInterface;
use App\Modules\DriverPayroll\Models\DriverAttendance;
use App\Modules\DriverPayroll\Requests\AttendanceRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DriverAttendanceController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DriverPayrollServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->attendances(), 'Lấy danh sách công chuyến thành công.');
    }

    public function show(DriverAttendance $attendance): JsonResponse
    {
        return $this->success($this->service->attendance($attendance), 'Lấy công chuyến thành công.');
    }

    public function update(AttendanceRequest $request, DriverAttendance $attendance): JsonResponse
    {
        return $this->success($this->service->updateAttendance($attendance, $request->validated()), 'Cập nhật công chuyến thành công.');
    }

    public function confirm(DriverAttendance $attendance): JsonResponse
    {
        return $this->success($this->service->confirmAttendance($attendance), 'Đã xác nhận công chuyến.');
    }
}
