<?php

namespace App\Modules\MasterData\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Interfaces\VehicleServiceInterface;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Requests\CreateVehicleRequest;
use App\Modules\MasterData\Requests\UpdateVehicleRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class VehicleController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VehicleServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->vehicles(), 'Lấy danh sách xe thành công.');
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return $this->success($this->service->vehicle($vehicle), 'Lấy thông tin xe thành công.');
    }

    public function store(CreateVehicleRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->create($request->toDTO()),
            'Tạo xe thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        return $this->success(
            $this->service->update($vehicle, $request->toDTO()),
            'Cập nhật xe thành công.',
        );
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->service->deactivate($vehicle);

        return $this->success(null, 'Ngừng hoạt động xe thành công.');
    }
}
