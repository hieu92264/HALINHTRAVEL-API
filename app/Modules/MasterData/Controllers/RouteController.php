<?php

namespace App\Modules\MasterData\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Interfaces\RouteServiceInterface;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Requests\CreateRouteRequest;
use App\Modules\MasterData\Requests\UpdateRouteRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RouteController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RouteServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->routes(), 'Lấy danh sách tuyến thành công.');
    }

    public function show(Route $route): JsonResponse
    {
        return $this->success($this->service->route($route), 'Lấy thông tin tuyến thành công.');
    }

    public function store(CreateRouteRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->create($request->toDTO()),
            'Tạo tuyến thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateRouteRequest $request, Route $route): JsonResponse
    {
        return $this->success(
            $this->service->update($route, $request->toDTO()),
            'Cập nhật tuyến thành công.',
        );
    }

    public function destroy(Route $route): JsonResponse
    {
        $this->service->deactivate($route);

        return $this->success(null, 'Ngừng hoạt động tuyến thành công.');
    }
}
