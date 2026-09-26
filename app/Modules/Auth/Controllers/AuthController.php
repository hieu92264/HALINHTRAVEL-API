<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Requests\LoginRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected readonly AuthServiceInterface $service)
    {

    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $result = $this->service->login($credentials);
        return $this->success($result, 'Login successful!');
    }

    public function me(): JsonResponse
    {
        $result = $this->service->me();
        return $this->success($result);
    }

    public function refresh(): JsonResponse
    {
        $result = $this->service->refresh();
        return $this->success($result);
    }

    public function logout(): JsonResponse
    {
        $this->service->logout();
        return $this->success(null, 'Logged out successfully.');
    }
}
