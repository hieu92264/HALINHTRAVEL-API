<?php

namespace App\Modules\MasterData\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Interfaces\VehicleTypeServiceInterface;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\MasterData\Requests\CreateVehicleTypeRequest;
use App\Modules\MasterData\Requests\UpdateVehicleTypeRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class VehicleTypeController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VehicleTypeServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->vehicleTypes(), 'Lấy danh sách loại xe thành công.');
    }

    public function options(): JsonResponse
    {
        return $this->success($this->service->options(), 'Lấy danh sách lựa chọn loại xe thành công.');
    }

    public function show(VehicleType $vehicleType): JsonResponse
    {
        return $this->success($this->service->vehicleType($vehicleType), 'Lấy thông tin loại xe thành công.');
    }

    public function store(CreateVehicleTypeRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->create($request->toDTO()),
            'Tạo loại xe thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateVehicleTypeRequest $request, VehicleType $vehicleType): JsonResponse
    {
        return $this->success(
            $this->service->update($vehicleType, $request->toDTO()),
            'Cập nhật loại xe thành công.',
        );
    }

    public function destroy(VehicleType $vehicleType): JsonResponse
    {
        $this->service->deactivate($vehicleType);

        return $this->success(null, 'Ngừng hoạt động loại xe thành công.');
    }
}
