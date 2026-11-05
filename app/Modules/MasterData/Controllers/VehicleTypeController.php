<?php

namespace App\Modules\MasterData\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\VehicleType;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class VehicleTypeController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(VehicleType::query()->orderBy('code')->get()->map(fn (VehicleType $type) => $this->data($type))->all());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $type = VehicleType::query()->create($data);

        return $this->success($this->data($type), 'Tạo loại xe thành công.', Response::HTTP_CREATED);
    }

    public function update(Request $request, VehicleType $vehicleType): JsonResponse
    {
        $data = $this->validateData($request, $vehicleType, true);
        $vehicleType->fill($data)->save();

        return $this->success($this->data($vehicleType->fresh()), 'Cập nhật loại xe thành công.');
    }

    public function destroy(VehicleType $vehicleType): JsonResponse
    {
        if ($vehicleType->vehicles()->exists() || $vehicleType->routeRates()->exists()) {
            abort(409, 'Không thể ngừng loại xe đang được sử dụng.');
        }
        $vehicleType->forceFill(['is_active' => false])->save();

        return $this->success(null, 'Ngừng hoạt động loại xe thành công.');
    }

    private function validateData(Request $request, ?VehicleType $type = null, bool $partial = false): array
    {
        $prefix = $partial ? 'sometimes' : 'required';
        $data = $request->validate([
            'code' => [$prefix, 'string', 'max:20', Rule::unique('vehicle_types', 'code')->ignore($type?->id)],
            'name' => [$prefix, 'string', 'max:100'],
            'tourism_wage_rate' => [$prefix, 'numeric', 'min:0', 'max:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['code'])) {
            $data['code'] = trim($data['code']);
        }

        return $data;
    }

    private function data(VehicleType $type): array
    {
        return $type->only(['id', 'code', 'name', 'tourism_wage_rate', 'is_active']);
    }
}
