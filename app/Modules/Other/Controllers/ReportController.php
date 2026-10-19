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

    public function vehicleProfit(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'vehicle-profit', 'Báo cáo lợi nhuận xe', $this->reports->vehicleProfit($request->filters()));
    }

    public function customerDebts(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'customer-debts', 'Công nợ khách hàng', ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => [], 'rows' => $this->debts->customerDebts($request->validated('from_date'), $request->validated('to_date'))]);
    }

    public function partnerDebts(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'partner-debts', 'Công nợ đối tác', ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => [], 'rows' => $this->debts->partnerDebts($request->validated('from_date'), $request->validated('to_date'))]);
    }

    public function externalDriverDebts(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'external-driver-debts', 'Công nợ thuê lái', ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => [], 'rows' => $this->debts->externalDriverDebts($request->validated('from_date'), $request->validated('to_date'))]);
    }

    public function customerDebtDetails(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $customerId = $request->validated('customer_id');
        abort_unless($customerId, 422, 'Báo cáo chi tiết yêu cầu khách hàng.');
        $detail = $this->debts->customerDebtDetails((int) $customerId, $request->validated('from_date'), $request->validated('to_date'));

        return $this->respond($request, 'customer-debt-details', 'Chi tiết công nợ khách hàng', ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => ['opening_balance' => $detail['opening_balance'], 'closing_balance' => $detail['closing_balance']], 'rows' => $detail['rows'], 'subject_name' => $detail['subject_name']]);
    }

    public function partnerDebtDetails(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $partnerId = $request->validated('partner_id');
        abort_unless($partnerId, 422, 'Báo cáo chi tiết yêu cầu chủ xe/hãng vận tải.');
        $detail = $this->debts->partnerDebtDetails((int) $partnerId, $request->validated('from_date'), $request->validated('to_date'));

        return $this->respond($request, 'partner-debt-details', 'Chi tiết công nợ chủ xe', ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => ['opening_balance' => $detail['opening_balance'], 'closing_balance' => $detail['closing_balance']], 'rows' => $detail['rows'], 'subject_name' => $detail['subject_name']]);
    }

    public function payrollDetails(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        abort_unless($request->validated('payroll_id'), 422, 'Báo cáo chi tiết yêu cầu kỳ lương.');

        return $this->respond($request, 'driver-payroll-details', 'Chi tiết lương lái xe', $this->reports->payrollDetails($request->filters()));
    }

    private function respond(ReportRequest $request, string $slug, string $message, array $data): JsonResponse|BinaryFileResponse
    {
        if ($request->validated('format') !== 'xlsx') {
            return $this->success($data, $message.' thành công.');
        }
        $excluded = ['id', 'schedule_id', 'details', 'expense_breakdown'];
        $fields = array_values(array_diff(array_keys($data['rows'][0] ?? []), $excluded));
        $rows = collect($data['rows'])->map(function (array $row) use ($fields): array {
            return collect($row)->only($fields)
                ->map(fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value)
                ->values()
                ->all();
        })->all();
        $headings = array_map(fn (string $key) => str_replace('_', ' ', ucfirst($key)), $fields);
        $period = sprintf('Từ ngày %s đến ngày %s', $request->validated('from_date'), $request->validated('to_date'));

        $title = isset($data['subject_name']) ? $message.': '.$data['subject_name'] : $message;

        return Excel::download(new ReportExport($title, $period, $headings, $rows, $data['totals'] ?? []), sprintf('%s_%s_%s.xlsx', $slug, $request->validated('from_date'), $request->validated('to_date')));
    }
}
