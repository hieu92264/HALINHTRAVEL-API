<?php

namespace App\Modules\MasterData\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Interfaces\DriverServiceInterface;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Requests\CreateDriverRequest;
use App\Modules\MasterData\Requests\UpdateDriverRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DriverController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DriverServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->drivers(), 'Lấy danh sách tài xế thành công.');
    }

    public function show(Driver $driver): JsonResponse
    {
        return $this->success($this->service->driver($driver), 'Lấy thông tin tài xế thành công.');
    }

    public function store(CreateDriverRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->create($request->toDTO()),
            'Tạo tài xế thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        return $this->success(
            $this->service->update($driver, $request->toDTO()),
            'Cập nhật tài xế thành công.',
        );
    }

    public function destroy(Driver $driver): JsonResponse
    {
        $this->service->deactivate($driver);

        return $this->success(null, 'Ngừng hoạt động tài xế thành công.');
    }
}
