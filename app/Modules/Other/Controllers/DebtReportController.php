<?php

namespace App\Modules\Other\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Other\Interfaces\DebtReportServiceInterface;
use App\Modules\Other\Requests\DebtReportRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DebtReportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DebtReportServiceInterface $service) {}

    public function customerDebts(DebtReportRequest $request): JsonResponse
    {
        return $this->success($this->service->customerDebts($request->validated('from_date'), $request->validated('to_date')), 'Lấy công nợ khách hàng thành công.');
    }

    public function partnerDebts(DebtReportRequest $request): JsonResponse
    {
        return $this->success($this->service->partnerDebts($request->validated('from_date'), $request->validated('to_date')), 'Lấy công nợ đối tác thành công.');
    }
}
