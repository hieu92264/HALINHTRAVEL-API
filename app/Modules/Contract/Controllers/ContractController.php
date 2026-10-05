<?php

namespace App\Modules\Contract\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contract\Interfaces\ContractServiceInterface;
use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Requests\StoreContractFromQuotationRequest;
use App\Modules\Contract\Requests\StoreContractRequest;
use App\Modules\Contract\Requests\UpdateContractRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContractController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ContractServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->getList(), 'Lấy danh sách hợp đồng thành công.');
    }

    public function show(Contract $contract): JsonResponse
    {
        return $this->success($this->service->getDetail($contract), 'Lấy thông tin hợp đồng thành công.');
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        return $this->success($this->service->store($request->toDTO()), 'Tạo hợp đồng thành công.', Response::HTTP_CREATED);
    }

    public function fromQuotation(StoreContractFromQuotationRequest $request): JsonResponse
    {
        return $this->success($this->service->fromQuotation($request->toDTO()), 'Tạo hợp đồng từ báo giá thành công.', Response::HTTP_CREATED);
    }

    public function update(UpdateContractRequest $request, Contract $contract): JsonResponse
    {
        return $this->success($this->service->update($contract, $request->toDTO()), 'Cập nhật hợp đồng thành công.');
    }

    public function destroy(Contract $contract): JsonResponse
    {
        $this->service->delete($contract);

        return $this->success(null, 'Ngừng hoạt động hợp đồng thành công.');
    }

    public function activate(Contract $contract): JsonResponse
    {
        return $this->success($this->service->activate($contract), 'Kích hoạt hợp đồng thành công.');
    }

    public function complete(Contract $contract): JsonResponse
    {
        return $this->success($this->service->complete($contract), 'Hoàn thành hợp đồng thành công.');
    }

    public function cancel(Contract $contract): JsonResponse
    {
        return $this->success($this->service->cancel($contract), 'Hủy hợp đồng thành công.');
    }
}
