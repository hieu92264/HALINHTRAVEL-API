<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Interfaces\FinanceServiceInterface;
use App\Modules\Finance\Models\Receipt;
use App\Modules\Finance\Requests\ReceiptRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FinanceServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->receipts(), 'Lấy danh sách phiếu thu thành công.');
    }

    public function show(Receipt $receipt): JsonResponse
    {
        return $this->success($this->service->receipt($receipt), 'Lấy phiếu thu thành công.');
    }

    public function store(ReceiptRequest $request): JsonResponse
    {
        return $this->success($this->service->createReceipt($request->validated()), 'Tạo phiếu thu thành công.', Response::HTTP_CREATED);
    }

    public function update(ReceiptRequest $request, Receipt $receipt): JsonResponse
    {
        return $this->success($this->service->updateReceipt($receipt, $request->validated()), 'Cập nhật phiếu thu thành công.');
    }

    public function destroy(Receipt $receipt): JsonResponse
    {
        $this->service->deactivateReceipt($receipt);

        return $this->success(null, 'Đã ngừng hoạt động phiếu thu.');
    }

    public function lock(Receipt $receipt): JsonResponse
    {
        return $this->success($this->service->lockReceipt($receipt), 'Đã khóa phiếu thu.');
    }
}
