<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\User;
use App\Modules\Dashboard\Interfaces\DashboardOverviewServiceInterface;
use App\Modules\Dashboard\Requests\DashboardOverviewRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardOverviewController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DashboardOverviewServiceInterface $service) {}

    public function __invoke(DashboardOverviewRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            $this->service->overview($user, $request->toDTO()),
            'Lấy dữ liệu tổng quan thành công.',
        );
    }
}
