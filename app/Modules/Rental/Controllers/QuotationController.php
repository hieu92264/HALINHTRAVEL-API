<?php

namespace App\Modules\Rental\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rental\Interfaces\QuotationServiceInterface;
use App\Modules\Rental\Models\Quotation;
use App\Modules\Rental\Requests\RecordQuotationResponseRequest;
use App\Modules\Rental\Requests\RejectQuotationResponseRequest;
use App\Modules\Rental\Requests\StoreQuotationRequest;
use App\Modules\Rental\Requests\UpdateQuotationRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class QuotationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly QuotationServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->getList(), 'Lấy danh sách báo giá thành công.');
    }

    public function show(Quotation $quotation): JsonResponse
    {
        return $this->success($this->service->getDetail($quotation), 'Lấy thông tin báo giá thành công.');
    }

    public function store(StoreQuotationRequest $request): JsonResponse
    {
        return $this->success($this->service->store($request->toDTO()), 'Tạo báo giá thành công.', Response::HTTP_CREATED);
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): JsonResponse
    {
        return $this->success($this->service->update($quotation, $request->toDTO()), 'Cập nhật báo giá thành công.');
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $this->service->delete($quotation);

        return $this->success(null, 'Xóa báo giá thành công.');
    }

    public function send(Quotation $quotation): JsonResponse
    {
        return $this->success($this->service->send($quotation), 'Đã gửi báo giá qua email.');
    }

    public function expire(Quotation $quotation): JsonResponse
    {
        return $this->success($this->service->expire($quotation), 'Đã cập nhật báo giá hết hạn.');
    }

    public function recordCustomerResponse(RecordQuotationResponseRequest $request, Quotation $quotation): JsonResponse
    {
        $userName = (string) $request->user('api')?->user_name;

        return $this->success(
            $this->service->recordCustomerResponse(
                $quotation,
                $request->boolean('accepted'),
                $request->validated('note'),
                $userName,
            ),
            'Đã ghi nhận phản hồi của khách hàng.',
        );
    }

    public function response(string $token): JsonResponse
    {
        return $this->success($this->service->responseSummary($token), 'Lấy thông tin báo giá thành công.');
    }

    public function acceptResponse(string $token): JsonResponse
    {
        return $this->success($this->service->acceptResponse($token), 'Đã chấp nhận báo giá.');
    }

    public function rejectResponse(RejectQuotationResponseRequest $request, string $token): JsonResponse
    {
        return $this->success($this->service->rejectResponse($token, $request->validated('note')), 'Đã từ chối báo giá.');
    }
}
