<?php

namespace App\Modules\Rental\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rental\Interfaces\RentalRequestServiceInterface;
use App\Modules\Rental\Models\RentalRequest;
use App\Modules\Rental\Requests\StoreRentalRequest;
use App\Modules\Rental\Requests\UpdateRentalRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RentalRequestController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RentalRequestServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->getList(), 'Lấy danh sách yêu cầu thuê xe thành công.');
    }

    public function show(RentalRequest $rentalRequest): JsonResponse
    {
        return $this->success($this->service->getDetail($rentalRequest), 'Lấy thông tin yêu cầu thuê xe thành công.');
    }

    public function store(StoreRentalRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->store($request->toDTO()),
            'Tạo yêu cầu thuê xe thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateRentalRequest $request, RentalRequest $rentalRequest): JsonResponse
    {
        return $this->success(
            $this->service->update($rentalRequest, $request->toDTO()),
            'Cập nhật yêu cầu thuê xe thành công.',
        );
    }

    public function destroy(RentalRequest $rentalRequest): JsonResponse
    {
        $this->service->delete($rentalRequest->id);

        return $this->success(null, 'Ngừng hoạt động yêu cầu thuê xe thành công.');
    }

    public function markQuoted(RentalRequest $rentalRequest): JsonResponse
    {
        return $this->success($this->service->markQuoted($rentalRequest->id), 'Cập nhật yêu cầu đã báo giá thành công.');
    }

    public function accept(RentalRequest $rentalRequest): JsonResponse
    {
        return $this->success($this->service->acceptQuoted($rentalRequest->id), 'Chấp nhận yêu cầu thuê xe thành công.');
    }

    public function reject(RentalRequest $rentalRequest): JsonResponse
    {
        return $this->success($this->service->rejectQuoted($rentalRequest->id), 'Từ chối yêu cầu thuê xe thành công.');
    }
}
