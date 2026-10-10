<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Interfaces\FinanceServiceInterface;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Requests\PartnerPaymentRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PartnerPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FinanceServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->partnerPayments(), 'Lấy danh sách chi trả đối tác thành công.');
    }

    public function show(PartnerPayment $partnerPayment): JsonResponse
    {
        return $this->success($this->service->partnerPayment($partnerPayment), 'Lấy chi trả đối tác thành công.');
    }

    public function store(PartnerPaymentRequest $request): JsonResponse
    {
        return $this->success($this->service->createPartnerPayment($request->validated()), 'Tạo chi trả đối tác thành công.', Response::HTTP_CREATED);
    }

    public function update(PartnerPaymentRequest $request, PartnerPayment $partnerPayment): JsonResponse
    {
        return $this->success($this->service->updatePartnerPayment($partnerPayment, $request->validated()), 'Cập nhật chi trả đối tác thành công.');
    }

    public function destroy(PartnerPayment $partnerPayment): JsonResponse
    {
        $this->service->deactivatePartnerPayment($partnerPayment);

        return $this->success(null, 'Đã ngừng hoạt động chi trả đối tác.');
    }

    public function lock(PartnerPayment $partnerPayment): JsonResponse
    {
        return $this->success($this->service->lockPartnerPayment($partnerPayment), 'Đã khóa chi trả đối tác.');
    }
}
