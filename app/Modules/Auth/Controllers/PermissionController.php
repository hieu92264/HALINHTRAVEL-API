<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Interfaces\AccessManagementServiceInterface;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Requests\StorePermissionRequest;
use App\Modules\Auth\Requests\UpdatePermissionRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AccessManagementServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->service->permissions($this->perPage($request)));
    }

    public function show(Permission $permission): JsonResponse
    {
        return $this->success($this->service->permission($permission));
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        return $this->success($this->service->createPermission($request->validated()), 'Permission created.', Response::HTTP_CREATED);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        return $this->success($this->service->updatePermission($permission, $request->validated()), 'Permission updated.');
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $this->service->deletePermission($permission);

        return $this->success(null, 'Permission deleted.');
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
