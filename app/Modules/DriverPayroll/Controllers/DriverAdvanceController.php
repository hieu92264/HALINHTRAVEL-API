<?php

namespace App\Modules\DriverPayroll\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DriverPayroll\Interfaces\DriverPayrollServiceInterface;
use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\DriverPayroll\Requests\DriverAdvanceRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverAdvanceController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DriverPayrollServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->advances(), 'Lấy danh sách tạm ứng thành công.');
    }

    public function show(DriverAdvance $advance): JsonResponse
    {
        return $this->success($this->service->advance($advance), 'Lấy tạm ứng thành công.');
    }

    public function store(DriverAdvanceRequest $request): JsonResponse
    {
        return $this->success($this->service->createAdvance($request->validated()), 'Tạo tạm ứng thành công.', Response::HTTP_CREATED);
    }

    public function update(DriverAdvanceRequest $request, DriverAdvance $advance): JsonResponse
    {
        return $this->success($this->service->updateAdvance($advance, $request->validated()), 'Cập nhật tạm ứng thành công.');
    }

    public function destroy(DriverAdvance $advance): JsonResponse
    {
        $this->service->deactivateAdvance($advance);

        return $this->success(null, 'Đã ngừng hoạt động tạm ứng.');
    }

    public function confirm(Request $request, DriverAdvance $advance): JsonResponse
    {
        return $this->success($this->service->confirmAdvance($advance, $request->user('api')), 'Đã xác nhận tạm ứng.');
    }
}
