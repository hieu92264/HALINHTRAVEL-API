<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Interfaces\AccessManagementServiceInterface;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Requests\StoreUserRequest;
use App\Modules\Auth\Requests\SyncPermissionsRequest;
use App\Modules\Auth\Requests\SyncRolesRequest;
use App\Modules\Auth\Requests\UpdateUserRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AccessManagementServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->service->users($this->perPage($request)), 'Lấy danh sách người dùng thành công.');
    }

    public function all(): JsonResponse
    {
        return $this->success($this->service->allUsers(), 'Lấy danh sách người dùng thành công.');
    }

    public function show(User $user): JsonResponse
    {
        return $this->success($this->service->user($user), 'Lấy thông tin người dùng thành công.');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        return $this->success($this->service->createUser($request->validated()), 'Tạo người dùng thành công.', Response::HTTP_CREATED);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        return $this->success($this->service->updateUser($this->actor($request), $user, $request->validated()), 'Cập nhật người dùng thành công.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->service->deactivateUser($this->actor($request), $user);

        return $this->success(null, 'Ngừng hoạt động người dùng thành công.');
    }

    public function syncRoles(SyncRolesRequest $request, User $user): JsonResponse
    {
        return $this->success($this->service->syncUserRoles($this->actor($request), $user, $request->validated('role_ids')), 'Cập nhật vai trò người dùng thành công.');
    }

    public function syncPermissions(SyncPermissionsRequest $request, User $user): JsonResponse
    {
        return $this->success($this->service->syncUserPermissions($user, $request->validated('permission_ids')), 'Cập nhật quyền người dùng thành công.');
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        return $user;
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
