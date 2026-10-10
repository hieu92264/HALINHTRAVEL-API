<?php

namespace App\Modules\DriverPayroll\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DriverPayroll\Interfaces\DriverPayrollServiceInterface;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\DriverPayroll\Models\PayrollItem;
use App\Modules\DriverPayroll\Requests\PayrollItemRequest;
use App\Modules\DriverPayroll\Requests\PayrollRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayrollController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DriverPayrollServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->payrolls(), 'Lấy danh sách kỳ lương thành công.');
    }

    public function show(Payroll $payroll): JsonResponse
    {
        return $this->success($this->service->payroll($payroll), 'Lấy kỳ lương thành công.');
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        return $this->success($this->service->createPayroll($request->validated()), 'Tạo kỳ lương thành công.', Response::HTTP_CREATED);
    }

    public function update(PayrollRequest $request, Payroll $payroll): JsonResponse
    {
        return $this->success($this->service->updatePayroll($payroll, $request->validated()), 'Cập nhật kỳ lương thành công.');
    }

    public function calculate(Payroll $payroll): JsonResponse
    {
        return $this->success($this->service->calculate($payroll), 'Đã tính kỳ lương.');
    }

    public function updateItem(PayrollItemRequest $request, Payroll $payroll, PayrollItem $payrollItem): JsonResponse
    {
        return $this->success($this->service->updatePayrollItem($payroll, $payrollItem, $request->validated()), 'Đã điều chỉnh item lương.');
    }

    public function approve(Request $request, Payroll $payroll): JsonResponse
    {
        return $this->success($this->service->approve($payroll, $request->user('api')), 'Đã duyệt kỳ lương.');
    }

    public function markPaid(Payroll $payroll): JsonResponse
    {
        return $this->success($this->service->markPaid($payroll), 'Đã đánh dấu kỳ lương đã trả.');
    }

    public function lock(Payroll $payroll): JsonResponse
    {
        return $this->success($this->service->lock($payroll), 'Đã khóa kỳ lương.');
    }
}
