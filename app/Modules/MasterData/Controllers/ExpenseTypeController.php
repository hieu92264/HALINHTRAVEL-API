<?php

namespace App\Modules\MasterData\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Interfaces\ExpenseTypeServiceInterface;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Requests\CreateExpenseTypeRequest;
use App\Modules\MasterData\Requests\UpdateExpenseTypeRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExpenseTypeController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ExpenseTypeServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->expenseTypes(), 'Lấy danh sách loại chi phí thành công.');
    }

    public function show(ExpenseType $expenseType): JsonResponse
    {
        return $this->success($this->service->expenseType($expenseType), 'Lấy thông tin loại chi phí thành công.');
    }

    public function store(CreateExpenseTypeRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->create($request->toDTO()),
            'Tạo loại chi phí thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateExpenseTypeRequest $request, ExpenseType $expenseType): JsonResponse
    {
        return $this->success(
            $this->service->update($expenseType, $request->toDTO()),
            'Cập nhật loại chi phí thành công.',
        );
    }

    public function destroy(ExpenseType $expenseType): JsonResponse
    {
        $this->service->deactivate($expenseType);

        return $this->success(null, 'Ngừng hoạt động loại chi phí thành công.');
    }
}
