<?php

namespace App\Modules\MasterData\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Interfaces\PartnerServiceInterface;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Requests\CreatePartnerRequest;
use App\Modules\MasterData\Requests\UpdatePartnerRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PartnerController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PartnerServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->partners(), 'Lấy danh sách đối tác thành công.');
    }

    public function options(): JsonResponse
    {
        return $this->success($this->service->options(), 'Lấy danh sách lựa chọn đối tác thành công.');
    }

    public function show(Partner $partner): JsonResponse
    {
        return $this->success($this->service->partner($partner), 'Lấy thông tin đối tác thành công.');
    }

    public function store(CreatePartnerRequest $request): JsonResponse
    {
        return $this->success(
            $this->service->create($request->toDTO()),
            'Tạo đối tác thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdatePartnerRequest $request, Partner $partner): JsonResponse
    {
        return $this->success(
            $this->service->update($partner, $request->toDTO()),
            'Cập nhật đối tác thành công.',
        );
    }

    public function destroy(Partner $partner): JsonResponse
    {
        $this->service->deactivate($partner);

        return $this->success(null, 'Ngừng hoạt động đối tác thành công.');
    }
}
