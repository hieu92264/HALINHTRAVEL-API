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

    public function __construct(private readonly AccessManagementServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->service->roles($this->perPage($request)), 'Lấy danh sách vai trò thành công.');
    }

    public function all(): JsonResponse
    {
        return $this->success($this->service->allRoles(), 'Lấy danh sách vai trò thành công.');
    }

    public function show(Role $role): JsonResponse
    {
        return $this->success($this->service->role($role), 'Lấy thông tin vai trò thành công.');
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        return $this->success($this->service->createRole($request->validated()), 'Tạo vai trò thành công.', Response::HTTP_CREATED);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        return $this->success($this->service->updateRole($role, $request->validated()), 'Cập nhật vai trò thành công.');
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->service->deleteRole($role);

        return $this->success(null, 'Xóa vai trò thành công.');
    }

    public function syncPermissions(SyncPermissionsRequest $request, Role $role): JsonResponse
    {
        return $this->success($this->service->syncRolePermissions($role, $request->validated('permission_ids')), 'Cập nhật quyền của vai trò thành công.');
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
