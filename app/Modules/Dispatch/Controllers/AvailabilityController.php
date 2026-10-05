<?php

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Interfaces\AvailabilityServiceInterface;
use App\Modules\Dispatch\Requests\CheckAvailabilityRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AvailabilityServiceInterface $service) {}

    public function __invoke(CheckAvailabilityRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->check($request->toDTO()),
            'Kiểm tra năng lực xe và tài xế thành công.',
        );
    }
}
