<?php

namespace App\Modules\MasterData\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Interfaces\RouteRateServiceInterface;
use App\Modules\MasterData\Models\RouteRate;
use App\Modules\MasterData\Requests\CreateRouteRateRequest;
use App\Modules\MasterData\Requests\LookupRouteRateRequest;
use App\Modules\MasterData\Requests\UpdateRouteRateRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RouteRateController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RouteRateServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->routeRates(), 'Lấy danh sách giá tuyến thành công.');
    }

    public function lookup(LookupRouteRateRequest $request): JsonResponse
    {
        $criteria = $request->criteria();

        return $this->success(
            $this->service->lookup($criteria['route_id'], $criteria['vehicle_type_id'], $criteria['at_date']),
            'Tra cứu giá tuyến thành công.',
        );
    }

    public function show(RouteRate $routeRate): JsonResponse
    {
        return $this->success($this->service->routeRate($routeRate), 'Lấy thông tin giá tuyến thành công.');
    }

    public function store(CreateRouteRateRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->create($request->toDTO()),
            'Tạo giá tuyến thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateRouteRateRequest $request, RouteRate $routeRate): JsonResponse
    {
        return $this->success(
            $this->service->update($routeRate, $request->toDTO()),
            'Cập nhật giá tuyến thành công.',
        );
    }

    public function destroy(RouteRate $routeRate): JsonResponse
    {
        $this->service->deactivate($routeRate);

        return $this->success(null, 'Ngừng hoạt động giá tuyến thành công.');
    }
}
