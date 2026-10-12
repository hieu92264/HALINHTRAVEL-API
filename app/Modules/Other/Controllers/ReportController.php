<?php

namespace App\Modules\Other\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Other\Exports\ReportExport;
use App\Modules\Other\Interfaces\DebtReportServiceInterface;
use App\Modules\Other\Interfaces\ReportServiceInterface;
use App\Modules\Other\Requests\ReportRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReportServiceInterface $reports, private readonly DebtReportServiceInterface $debts) {}

    public function fixedTrips(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'fixed-trips', 'Báo cáo xe chạy tuyến', $this->reports->fixedTrips($request->filters()));
    }

    public function tourismTrips(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'tourism-trips', 'Báo cáo xe du lịch/công tác', $this->reports->tourismTrips($request->filters()));
    }

    public function cashflow(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'cashflow', 'Báo cáo thu chi', $this->reports->cashflow($request->filters()));
    }

    public function expenses(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'expenses', 'Báo cáo chi phí', $this->reports->expenses($request->filters()));
    }

    public function payroll(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'driver-payroll', 'Báo cáo lương', $this->reports->payroll($request->filters()));
    }

    public function profitLoss(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'profit-loss', 'Báo cáo lợi nhuận', $this->reports->profitLoss($request->filters()));
    }

    public function customerDebts(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'customer-debts', 'Công nợ khách hàng', ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => [], 'rows' => $this->debts->customerDebts($request->validated('from_date'), $request->validated('to_date'))]);
    }

    public function partnerDebts(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'partner-debts', 'Công nợ đối tác', ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => [], 'rows' => $this->debts->partnerDebts($request->validated('from_date'), $request->validated('to_date'))]);
    }

    private function respond(ReportRequest $request, string $slug, string $message, array $data): JsonResponse|BinaryFileResponse
    {
        if ($request->validated('format') !== 'xlsx') {
            return $this->success($data, $message.' thành công.');
        }
        $rows = collect($data['rows'])->map(function (array $row): array {
            return collect($row)
                ->except(['id', 'schedule_id', 'details', 'expense_breakdown'])
                ->map(fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value)
                ->values()
                ->all();
        })->all();
        $headings = array_keys($rows[0] ?? []);

        return Excel::download(new ReportExport($headings, $rows), sprintf('%s_%s_%s.xlsx', $slug, $request->validated('from_date'), $request->validated('to_date')));
    }
}
