<?php

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Interfaces\DispatchOrderServiceInterface;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\Dispatch\Requests\ConfirmCompletionRequest;
use App\Modules\Dispatch\Requests\ReportCompletionRequest;
use App\Modules\Dispatch\Requests\ReturnCompletionRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DispatchOrderController extends Controller
{
    use ApiResponse;
    public function __construct(private readonly DispatchOrderServiceInterface $service) {}
    public function index(): JsonResponse { return $this->success($this->service->getList(), 'Lấy danh sách lệnh điều xe thành công.'); }
    public function show(DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->getDetail($dispatchOrder), 'Lấy chi tiết lệnh điều xe thành công.'); }
    public function issue(Request $request, TripSchedule $tripSchedule): JsonResponse { return $this->success($this->service->issue($tripSchedule, $request->user()), 'Phát hành lệnh điều xe thành công.', Response::HTTP_CREATED); }
    public function assign(DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->assign($dispatchOrder), 'Đã xác nhận phân công lệnh điều xe.'); }
    public function start(Request $request, DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->start($dispatchOrder, $request->user()), 'Đã bắt đầu chuyến xe.'); }
    public function report(ReportCompletionRequest $request, DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->reportCompletion($dispatchOrder, $request->toDTO(), $request->user()), 'Đã gửi báo cáo hoàn tất.'); }
    public function confirm(ConfirmCompletionRequest $request, DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->confirmCompletion($dispatchOrder, $request->toDTO(), $request->user()), 'Đã xác nhận hoàn tất chuyến xe.'); }
    public function returnCompletion(ReturnCompletionRequest $request, DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->returnCompletion($dispatchOrder, $request->validated('review_note')), 'Đã trả báo cáo cho tài xế bổ sung.'); }
    public function cancel(DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->cancel($dispatchOrder), 'Đã hủy lệnh điều xe.'); }
    public function myIndex(Request $request): JsonResponse { return $this->success($this->service->getMyOrders($request->user()), 'Lấy lệnh của tôi thành công.'); }
    public function myShow(Request $request, DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->getMyOrder($dispatchOrder, $request->user()), 'Lấy chi tiết lệnh của tôi thành công.'); }
    public function myStart(Request $request, DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->start($dispatchOrder, $request->user(), true), 'Đã bắt đầu chuyến xe.'); }
    public function myReport(ReportCompletionRequest $request, DispatchOrder $dispatchOrder): JsonResponse { return $this->success($this->service->reportCompletion($dispatchOrder, $request->toDTO(), $request->user(), true), 'Đã gửi báo cáo hoàn tất.'); }
}
