<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Interfaces\FinanceServiceInterface;
use App\Modules\Finance\Models\DriverCashRemittance;
use App\Modules\Finance\Requests\DriverCashRemittanceRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DriverCashRemittanceController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FinanceServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->driverCashRemittances(), 'Lấy danh sách lái xe nộp tiền thành công.');
    }

    public function show(DriverCashRemittance $driverCashRemittance): JsonResponse
    {
        return $this->success($this->service->driverCashRemittance($driverCashRemittance), 'Lấy chứng từ lái xe nộp tiền thành công.');
    }

    public function store(DriverCashRemittanceRequest $request): JsonResponse
    {
        return $this->success($this->service->createDriverCashRemittance($request->validated()), 'Tạo chứng từ lái xe nộp tiền thành công.', Response::HTTP_CREATED);
    }

    public function update(DriverCashRemittanceRequest $request, DriverCashRemittance $driverCashRemittance): JsonResponse
    {
        return $this->success($this->service->updateDriverCashRemittance($driverCashRemittance, $request->validated()), 'Cập nhật chứng từ lái xe nộp tiền thành công.');
    }

    public function destroy(DriverCashRemittance $driverCashRemittance): JsonResponse
    {
        $this->service->deactivateDriverCashRemittance($driverCashRemittance);

        return $this->success(null, 'Đã ngừng hoạt động chứng từ lái xe nộp tiền.');
    }
}
