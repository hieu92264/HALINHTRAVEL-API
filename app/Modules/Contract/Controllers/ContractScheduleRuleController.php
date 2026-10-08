<?php

namespace App\Modules\Contract\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contract\Interfaces\ContractScheduleRuleServiceInterface;
use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Models\ContractScheduleRule;
use App\Modules\Contract\Requests\GenerateTripSchedulesRequest;
use App\Modules\Contract\Requests\ReplaceContractScheduleDaysRequest;
use App\Modules\Contract\Requests\StoreContractScheduleRuleRequest;
use App\Modules\Contract\Requests\UpdateContractScheduleRuleRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContractScheduleRuleController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ContractScheduleRuleServiceInterface $service) {}

    public function index(Contract $contract): JsonResponse
    {
        return $this->success($this->service->getList($contract), 'Lấy danh sách quy tắc lịch thành công.');
    }

    public function show(ContractScheduleRule $scheduleRule): JsonResponse
    {
        return $this->success($this->service->getDetail($scheduleRule), 'Lấy thông tin quy tắc lịch thành công.');
    }

    public function store(StoreContractScheduleRuleRequest $request, Contract $contract): JsonResponse
    {
        return $this->success(
            $this->service->store($contract, $request->toDTO()),
            'Tạo quy tắc lịch thành công.',
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateContractScheduleRuleRequest $request, ContractScheduleRule $scheduleRule): JsonResponse
    {
        return $this->success($this->service->update($scheduleRule, $request->toDTO()), 'Cập nhật quy tắc lịch thành công.');
    }

    public function destroy(ContractScheduleRule $scheduleRule): JsonResponse
    {
        $this->service->delete($scheduleRule);

        return $this->success(null, 'Ngừng hoạt động quy tắc lịch thành công.');
    }

    public function replaceDays(ReplaceContractScheduleDaysRequest $request, ContractScheduleRule $scheduleRule): JsonResponse
    {
        return $this->success($this->service->replaceDays($scheduleRule, $request->toDTO()), 'Cập nhật ngày chạy lịch thành công.');
    }

    public function generateTripSchedules(GenerateTripSchedulesRequest $request, ContractScheduleRule $scheduleRule): JsonResponse
    {
        return $this->success(
            $this->service->generateTripSchedules($scheduleRule, $request->toDTO()),
            'Sinh lịch chuyến thành công.',
        );
    }
}
