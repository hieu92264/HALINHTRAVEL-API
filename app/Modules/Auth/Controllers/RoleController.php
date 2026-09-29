<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Interfaces\AccessManagementServiceInterface;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Requests\StoreRoleRequest;
use App\Modules\Auth\Requests\SyncPermissionsRequest;
use App\Modules\Auth\Requests\UpdateRoleRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AccessManagementServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->service->roles($this->perPage($request)));
    }

    public function show(Role $role): JsonResponse
    {
        return $this->success($this->service->role($role));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        return $this->success($this->service->createRole($request->validated()), 'Role created.', Response::HTTP_CREATED);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        return $this->success($this->service->updateRole($role, $request->validated()), 'Role updated.');
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->service->deleteRole($role);

        return $this->success(null, 'Role deleted.');
    }

    public function syncPermissions(SyncPermissionsRequest $request, Role $role): JsonResponse
    {
        return $this->success($this->service->syncRolePermissions($role, $request->validated('permission_ids')), 'Role permissions updated.');
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
