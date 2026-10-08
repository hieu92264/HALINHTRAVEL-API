<?php

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Interfaces\TripAssignmentServiceInterface;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\Dispatch\Requests\StoreTripAssignmentRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TripAssignmentController extends Controller
{
    use ApiResponse;
    public function __construct(private readonly TripAssignmentServiceInterface $service) {}
    public function index(TripSchedule $tripSchedule): JsonResponse { return $this->success($this->service->getList($tripSchedule), 'Lấy lịch sử phân công thành công.'); }
    public function store(StoreTripAssignmentRequest $request, TripSchedule $tripSchedule): JsonResponse { return $this->success($this->service->assign($tripSchedule, $request->toDTO(), $request->user()), 'Phân công chuyến thành công.', Response::HTTP_CREATED); }
    public function substitute(StoreTripAssignmentRequest $request, TripSchedule $tripSchedule): JsonResponse { return $this->success($this->service->substitute($tripSchedule, $request->toDTO(), $request->user()), 'Thay phân công thành công.'); }
    public function destroy(Request $request, TripAssignment $tripAssignment): JsonResponse { return $this->success($this->service->removeCurrent($tripAssignment), 'Gỡ phân công hiện hành thành công.'); }
}
