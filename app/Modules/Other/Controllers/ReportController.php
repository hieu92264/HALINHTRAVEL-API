<?php

namespace App\Modules\Other\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Other\Exports\CustomerDebtDetailWordExport;
use App\Modules\Other\Exports\CustomerDebtWordExport;
use App\Modules\Other\Exports\DebtSummaryExport;
use App\Modules\Other\Exports\DriverPayrollDetailWordExport;
use App\Modules\Other\Exports\DriverPayrollWordExport;
use App\Modules\Other\Exports\FixedTripWordExport;
use App\Modules\Other\Exports\ReportExport;
use App\Modules\Other\Exports\TourismTripWordExport;
use App\Modules\Other\Exports\VehicleProfitWordExport;
use App\Modules\Other\Interfaces\DebtReportServiceInterface;
use App\Modules\Other\Interfaces\ReportServiceInterface;
use App\Modules\Other\Requests\ReportRequest;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ReportServiceInterface $reports,
        private readonly DebtReportServiceInterface $debts,
        private readonly CustomerDebtDetailWordExport $customerDebtDetailWord,
        private readonly CustomerDebtWordExport $customerDebtWord,
        private readonly VehicleProfitWordExport $vehicleProfitWord,
        private readonly FixedTripWordExport $fixedTripWord,
        private readonly TourismTripWordExport $tourismTripWord,
        private readonly DriverPayrollWordExport $driverPayrollWord,
        private readonly DriverPayrollDetailWordExport $driverPayrollDetailWord,
    ) {}

    public function fixedTrips(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $data = $this->reports->fixedTrips($request->filters());
        $format = $request->validated('format');
        abort_if($format === 'xlsx', 422, 'Báo cáo xe chạy công nhân chỉ hỗ trợ xuất Word.');
        if ($format !== 'docx') {
            return $this->success($data, 'Báo cáo xe chạy công nhân thành công.');
        }

        $from = $request->validated('from_date');
        $to = $request->validated('to_date');
        $path = $this->fixedTripWord->create($data['rows'], $from, $to);

        return response()->download(
            $path,
            sprintf('Bao-cao-xe-chay-cong-nhan-tu-%s-den-%s.docx', $from, $to),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    public function tourismTrips(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $data = $this->reports->tourismTrips($request->filters());
        $format = $request->validated('format');
        abort_if($format === 'xlsx', 422, 'Báo cáo xe chạy du lịch chỉ hỗ trợ xuất Word.');
        if ($format !== 'docx') {
            return $this->success($data, 'Báo cáo xe chạy du lịch thành công.');
        }

        $from = $request->validated('from_date');
        $to = $request->validated('to_date');
        $path = $this->tourismTripWord->create($data['rows'], $from, $to);

        return response()->download(
            $path,
            sprintf('Bao-cao-xe-chay-du-lich-tu-%s-den-%s.docx', $from, $to),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
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
        $data = $this->reports->payroll($request->filters());
        $format = $request->validated('format');
        abort_if($format === 'xlsx', 422, 'Bảng lương lái xe chỉ hỗ trợ xuất Word.');
        if ($format !== 'docx') {
            return $this->success($data, 'Bảng lương lái xe thành công.');
        }

        $from = $request->validated('from_date');
        $to = $request->validated('to_date');
        $path = $this->driverPayrollWord->create($data['rows'], $from, $to);

        return response()->download(
            $path,
            sprintf('Bang-luong-lai-xe-tu-%s-den-%s.docx', $from, $to),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    public function profitLoss(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        return $this->respond($request, 'profit-loss', 'Báo cáo lợi nhuận', $this->reports->profitLoss($request->filters()));
    }

    /** Bảng lợi nhuận xe – preview JSON or Word document. */
    public function vehicleProfit(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $data = $this->reports->vehicleProfit($request->filters());

        $format = $request->validated('format');
        abort_if($format === 'xlsx', 422, 'Báo cáo lợi nhuận xe chỉ hỗ trợ xuất Word.');
        if ($format !== 'docx') {
            return $this->success($data, 'Báo cáo lợi nhuận xe thành công.');
        }

        $from = $request->validated('from_date');
        $to = $request->validated('to_date');
        $path = $this->vehicleProfitWord->create($data['rows'], $from, $to);

        return response()->download(
            $path,
            sprintf('Bang-loi-nhuan-xe-tu-%s-den-%s.docx', $from, $to),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    /**
     * Thống kê công nợ khách hàng (summary) – uses DebtSummaryExport.
     */
    public function customerDebts(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $data = ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => [], 'rows' => $this->debts->customerDebts($request->validated('from_date'), $request->validated('to_date'))];

        $format = $request->validated('format');
        abort_if($format === 'xlsx', 422, 'Báo cáo tổng hợp công nợ khách hàng chỉ hỗ trợ xuất Word.');
        if ($format !== 'docx') {
            return $this->success($data, 'Công nợ khách hàng thành công.');
        }

        $from = $request->validated('from_date');
        $to = $request->validated('to_date');
        $path = $this->customerDebtWord->create($data['rows'], $from, $to);

        return response()->download(
            $path,
            sprintf('Bang-tong-hop-cong-no-khach-hang-tu-%s-den-%s.docx', $from, $to),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    /**
     * Thống kê công nợ các chủ xe (summary) – uses DebtSummaryExport.
     */
    public function partnerDebts(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $data = ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => [], 'rows' => $this->debts->partnerDebts($request->validated('from_date'), $request->validated('to_date'))];

        $format = $request->validated('format');
        abort_if($format === 'xlsx', 422, 'Báo cáo tổng hợp công nợ chủ xe chỉ hỗ trợ xuất Word.');
        if ($format !== 'docx') {
            return $this->success($data, 'Công nợ đối tác thành công.');
        }

        $from = $request->validated('from_date');
        $to = $request->validated('to_date');
        $path = $this->customerDebtWord->create(
            $data['rows'],
            $from,
            $to,
            'THỐNG KÊ CÔNG NỢ CÁC CHỦ XE',
            'Hãng vận tải/Chủ xe',
            'paid_amount',
        );

        return response()->download(
            $path,
            sprintf('Thong-ke-cong-no-cac-chu-xe-tu-%s-den-%s.docx', $from, $to),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    /**
     * Công nợ thuê lái (summary) – reuses DebtSummaryExport with driver columns.
     */
    public function externalDriverDebts(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $data = ['filters' => $request->filters(), 'generated_at' => now()->toISOString(), 'totals' => [], 'rows' => $this->debts->externalDriverDebts($request->validated('from_date'), $request->validated('to_date'))];

        if ($request->validated('format') !== 'xlsx') {
            return $this->success($data, 'Công nợ thuê lái thành công.');
        }

        $from = $request->validated('from_date');
        $to = $request->validated('to_date');

        $rows = collect($data['rows'])->values()->map(fn (array $row, int $i) => [
            $i + 1,
            $row['name'] ?? '',
            $row['opening_balance'] ?? 0,
            $row['incurred_amount'] ?? 0,
            $row['paid_amount'] ?? 0,
            $row['closing_balance'] ?? 0,
        ])->all();

        $totals = [
            'Tổng cộng',
            '',
            collect($data['rows'])->sum('opening_balance'),
            collect($data['rows'])->sum('incurred_amount'),
            collect($data['rows'])->sum('paid_amount'),
            collect($data['rows'])->sum('closing_balance'),
        ];

        $period = sprintf('(Từ ngày %s đến ngày %s)', $from, $to);

        return Excel::download(
            new DebtSummaryExport(
                title: 'THỐNG KÊ CÔNG NỢ THUÊ LÁI',
                period: $period,
                subjectCol: 'Lái xe thuê',
                rows: $rows,
                totals: $totals,
                cityDateLine: $this->cityDateLine(),
            ),
            sprintf('external-driver-debts_%s_%s.xlsx', $from, $to),
        );
    }

    /**
     * Chi tiết công nợ khách hàng – preview JSON or Word document.
     */
    public function customerDebtDetails(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $customerId = $request->validated('customer_id');
        abort_unless($customerId, 422, 'Báo cáo chi tiết yêu cầu khách hàng.');
        $from = $request->validated('from_date');
        $to = $request->validated('to_date');
        $detail = $this->debts->customerDebtDetails((int) $customerId, $from, $to);

        $data = [
            'filters' => $request->filters(),
            'generated_at' => now()->toISOString(),
            'totals' => ['opening_balance' => $detail['opening_balance'], 'closing_balance' => $detail['closing_balance']],
            'rows' => $detail['rows'],
            'subject_name' => $detail['subject_name'],
        ];

        $format = $request->validated('format');
        abort_if($format === 'xlsx', 422, 'Báo cáo chi tiết công nợ khách hàng chỉ hỗ trợ xuất Word.');
        if ($format !== 'docx') {
            return $this->success($data, 'Chi tiết công nợ khách hàng thành công.');
        }

        $path = $this->customerDebtDetailWord->create($detail, $from, $to);
        $customer = Str::slug((string) ($detail['subject_name'] ?? ''), '-');
        $filename = sprintf(
            'Chi-tiet-cong-no-khach-hang-%s-tu-%s-den-%s.docx',
            $customer !== '' ? $customer : 'khach-hang',
            $from,
            $to,
        );

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    /** Chi tiết công nợ chủ xe – preview JSON or Word document. */
    public function partnerDebtDetails(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $partnerId = $request->validated('partner_id');
        abort_unless($partnerId, 422, 'Báo cáo chi tiết yêu cầu chủ xe/hãng vận tải.');
        $from = $request->validated('from_date');
        $to = $request->validated('to_date');
        $detail = $this->debts->partnerDebtDetails((int) $partnerId, $from, $to);

        $data = [
            'filters' => $request->filters(),
            'generated_at' => now()->toISOString(),
            'totals' => ['opening_balance' => $detail['opening_balance'], 'closing_balance' => $detail['closing_balance']],
            'rows' => $detail['rows'],
            'subject_name' => $detail['subject_name'],
        ];

        $format = $request->validated('format');
        abort_if($format === 'xlsx', 422, 'Báo cáo chi tiết công nợ chủ xe chỉ hỗ trợ xuất Word.');
        if ($format !== 'docx') {
            return $this->success($data, 'Chi tiết công nợ chủ xe thành công.');
        }

        $path = $this->customerDebtDetailWord->create(
            $detail,
            $from,
            $to,
            'CHI TIẾT CÔNG NỢ CHỦ XE',
            'Chủ xe',
            'partner-debt-detail-',
        );
        $partner = Str::slug((string) ($detail['subject_name'] ?? ''), '-');
        $filename = sprintf(
            'Chi-tiet-cong-no-chu-xe-%s-tu-%s-den-%s.docx',
            $partner !== '' ? $partner : 'chu-xe',
            $from,
            $to,
        );

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    public function payrollDetails(ReportRequest $request): JsonResponse|BinaryFileResponse
    {
        abort_unless($request->validated('payroll_id'), 422, 'Báo cáo chi tiết yêu cầu kỳ lương.');

        $data = $this->reports->payrollDetails($request->filters());
        $format = $request->validated('format');
        abort_if($format === 'xlsx', 422, 'Báo cáo chi tiết lương lái xe chỉ hỗ trợ xuất Word.');
        if ($format !== 'docx') {
            return $this->success($data, 'Chi tiết lương lái xe thành công.');
        }

        $from = $data['payroll']['from_date'] ?? $request->validated('from_date');
        $to = $data['payroll']['to_date'] ?? $request->validated('to_date');
        $path = $this->driverPayrollDetailWord->create($data['rows'], $data['totals'], $from, $to);
        $payroll = Str::slug((string) ($data['payroll']['code'] ?? ''), '-');

        return response()->download(
            $path,
            sprintf('Chi-tiet-luong-lai-xe-%s-tu-%s-den-%s.docx', $payroll !== '' ? $payroll : 'ky-'.$request->validated('payroll_id'), $from, $to),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    /**
     * Generic respond helper (used for reports that do not have a dedicated Export class yet).
     */
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

    /**
     * Build the city/date footer line (right column, italic).
     */
    private function cityDateLine(): string
    {
        $now = now();

        return sprintf('Hải Phòng, ngày %d tháng %d năm %d.', $now->day, $now->month, $now->year);
    }
}
