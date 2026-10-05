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

    public function index() {}

    public function show() {}

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

    public function destroy() {}

    public function markQuoted() {}

    public function accept() {}

    public function reject() {}
}
