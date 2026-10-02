<?php

namespace App\Modules\MasterData\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\DTOs\CreateCustomerData;
use App\Modules\MasterData\DTOs\UpdateCustomerData;
use App\Modules\MasterData\Interfaces\CustomerServiceInterface;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Requests\CreateCustomerRequest;
use App\Modules\MasterData\Requests\UpdateCustomerRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CustomerServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->customers(), 'Lấy danh sách khách hàng thành công.');
    }

    public function show(Customer $customer): JsonResponse
    {
        return $this->success($this->service->customer($customer), 'Lấy thông tin khách hàng thành công.');
    }

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->create(CreateCustomerData::fromValidated($request->validated())),
            'Tạo khách hàng thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        return $this->success(
            $this->service->update($customer, UpdateCustomerData::fromValidated($request->validated())),
            'Cập nhật khách hàng thành công.',
        );
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->service->deactivate($customer);

        return $this->success(null, 'Ngừng hoạt động khách hàng thành công.');
    }
}
