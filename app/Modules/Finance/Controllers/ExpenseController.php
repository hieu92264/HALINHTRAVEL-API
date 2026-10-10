<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Interfaces\FinanceServiceInterface;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Requests\ExpenseRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FinanceServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->expenses(), 'Lấy danh sách chi phí thành công.');
    }

    public function show(Expense $expense): JsonResponse
    {
        return $this->success($this->service->expense($expense), 'Lấy chi phí thành công.');
    }

    public function store(ExpenseRequest $request): JsonResponse
    {
        return $this->success($this->service->createExpense($request->validated()), 'Tạo chi phí thành công.', Response::HTTP_CREATED);
    }

    public function update(ExpenseRequest $request, Expense $expense): JsonResponse
    {
        return $this->success($this->service->updateExpense($expense, $request->validated()), 'Cập nhật chi phí thành công.');
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->service->deactivateExpense($expense);

        return $this->success(null, 'Đã ngừng hoạt động chi phí.');
    }

    public function lock(Expense $expense): JsonResponse
    {
        return $this->success($this->service->lockExpense($expense), 'Đã khóa chi phí.');
    }
}
