<?php

namespace App\Modules\Other\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Other\Requests\ReportRequest;
use App\Modules\Other\Services\RestoredReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use HieuDev92264\LaravelModules\Traits\ApiResponse;
use Illuminate\Support\Carbon;

class RestoredReportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RestoredReportService $service) {}

    public function businessTrips(ReportRequest $request): mixed
    {
        return $this->respond('businessTrips', $request, 'business-trips');
    }

    public function tourismTrips(ReportRequest $request): mixed
    {
        return $this->respond('tourismTrips', $request, 'tourism-trips');
    }

    public function customerDebts(ReportRequest $request): mixed
    {
        return $this->respond('customerDebts', $request, 'customer-debts');
    }

    public function partnerDebts(ReportRequest $request): mixed
    {
        return $this->respond('partnerDebts', $request, 'partner-debts');
    }

    public function driverPayroll(ReportRequest $request): mixed
    {
        return $this->respond('driverPayroll', $request, 'driver-payroll');
    }

    public function vehicleProfit(ReportRequest $request): mixed
    {
        return $this->respond('vehicleProfit', $request, 'vehicle-profit');
    }

    public function customerDebtDetails(ReportRequest $request): mixed
    {
        return $this->respond('customerDebtDetails', $request, 'customer-debt-details');
    }

    public function partnerDebtDetails(ReportRequest $request): mixed
    {
        return $this->respond('partnerDebtDetails', $request, 'partner-debt-details');
    }

    public function driverPayrollDetails(ReportRequest $request): mixed
    {
        return $this->respond('driverPayrollDetails', $request, 'driver-payroll-details');
    }

    private function respond(string $method, ReportRequest $request, string $filename): mixed
    {
        $filters = $request->filters();
        $report = $this->service->{$method}($filters);
        if ($request->validated('format') !== 'pdf') {
            return $this->success($report, 'Lấy báo cáo thành công.');
        }
        $pdf = Pdf::loadView('reports.pdf', ['report' => $report, 'pdf' => $this->pdfData($filename, $report), 'title' => $this->title($filename), 'fromDate' => Carbon::parse($filters['from_date'])->format('d/m/Y'), 'toDate' => Carbon::parse($filters['to_date'])->format('d/m/Y')])
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename.'-'.$filters['from_date'].'-'.$filters['to_date'].'.pdf');
    }

    private function title(string $filename): string
    {
        return match ($filename) {
            'business-trips' => 'BÁO CÁO THỐNG KÊ XE CHẠY CÔNG NHÂN',
            'tourism-trips' => 'BÁO CÁO THỐNG KÊ XE CHẠY DU LỊCH',
            'customer-debts' => 'BẢNG TỔNG HỢP CÔNG NỢ KHÁCH HÀNG',
            'partner-debts' => 'BẢNG TỔNG HỢP CÔNG NỢ CHỦ XE',
            'driver-payroll' => 'BẢNG LƯƠNG LÁI XE',
            'vehicle-profit' => 'BÁO CÁO LỢI NHUẬN XE',
            'customer-debt-details' => 'CHI TIẾT CÔNG NỢ KHÁCH HÀNG',
            'partner-debt-details' => 'CHI TIẾT CÔNG NỢ CHỦ XE',
            'driver-payroll-details' => 'CHI TIẾT LƯƠNG LÁI XE',
            default => 'BÁO CÁO',
        };
    }

    /** @return array{columns: array<string, string>, rows: array<int, array<string, string>>, totals: array<string, string>} */
    private function pdfData(string $filename, array $report): array
    {
        $columns = match ($filename) {
            'business-trips' => ['stt' => 'STT', 'date' => 'Ngày', 'vehicle_plate' => 'Biển số', 'customer_name' => 'Khách hàng', 'route_name' => 'Tên tuyến', 'pickup_time' => 'Giờ đi', 'return_time' => 'Giờ về', 'driver_name' => 'Tên lái xe', 'revenue' => 'Cước xe', 'driver_wage' => 'Lương lái xe', 'external_driver_cost' => 'Tiền thuê lái', 'partner_vehicle_cost' => 'Tiền thuê xe', 'external_vehicle_profit' => 'Lợi nhuận xe', 'partner_name' => 'Chủ xe'],
            'tourism-trips' => ['stt' => 'STT', 'date' => 'Ngày', 'vehicle_plate' => 'Biển số', 'journey' => 'Hành trình', 'driver_name' => 'Tên lái xe', 'revenue' => 'Cước xe', 'driver_wage_rate' => '% lương', 'driver_wage' => 'Lương lái xe', 'external_driver_cost' => 'Tiền thuê lái', 'hired_driver_name' => 'Tên lái thuê', 'driver_collected_amount' => 'Lái xe thu', 'driver_remitted_amount' => 'Lái xe nộp', 'company_collected_amount' => 'Công ty thu', 'remaining_receivable' => 'Còn lại phải thu'],
            'customer-debts' => ['stt' => 'STT', 'code' => 'Mã khách hàng', 'name' => 'Khách hàng', 'opening_balance' => 'Nợ đầu kỳ', 'fixed_trip_amount' => 'Cước xe công nhân', 'tourism_trip_amount' => 'Cước xe du lịch', 'incurred_amount' => 'Nợ phát sinh', 'received_amount' => 'Đã thu', 'closing_balance' => 'Nợ cuối kỳ'],
            'partner-debts' => ['stt' => 'STT', 'code' => 'Mã chủ xe', 'name' => 'Chủ xe', 'opening_balance' => 'Nợ đầu kỳ', 'partner_vehicle_cost' => 'Tiền thuê xe', 'incurred_amount' => 'Nợ phát sinh', 'paid_amount' => 'Đã chi trả', 'closing_balance' => 'Nợ cuối kỳ'],
            'customer-debt-details', 'partner-debt-details' => ['stt' => 'STT', 'date' => 'Ngày', 'document_no' => 'Số chứng từ', 'type' => 'Diễn giải', 'journey' => 'Hành trình', 'vehicle_plate' => 'Biển số', 'pickup_time' => 'Giờ đón', 'return_time' => 'Giờ về', 'note' => 'Ghi chú', 'debit' => 'Nợ phát sinh', 'credit' => 'Có phát sinh', 'balance' => 'Nợ cuối kỳ'],
            'driver-payroll' => ['stt' => 'STT', 'payroll_code' => 'Mã kỳ lương', 'driver_name' => 'Họ và tên', 'responsibility_allowance' => 'Lương trách nhiệm', 'fixed_trip_wage' => 'Lương lái công nhân', 'tourism_commission' => 'Lương lái du lịch', 'gross_salary' => 'Tổng cộng', 'advance_amount' => 'Tạm ứng', 'driver_collected_amount' => 'Lái xe thu', 'driver_remitted_amount' => 'Lái xe nộp', 'driver_expense_amount' => 'Lái xe chi', 'net_salary' => 'Thực lĩnh'],
            'driver-payroll-details' => ['stt' => 'STT', 'driver_name' => 'Lái xe', 'date' => 'Ngày', 'calculation_type' => 'Loại khoản', 'worker_run_no' => 'Lệnh xe CN', 'tourist_run_no' => 'Lệnh xe DL', 'journey' => 'Hành trình/Diễn giải', 'base_amount' => 'Cơ sở tính', 'rate' => 'Tỷ lệ', 'amount' => 'Số tiền'],
            'vehicle-profit' => ['stt' => 'STT', 'vehicle_plate' => 'Biển số', 'vehicle_type' => 'Loại xe', 'revenue' => 'Thu cước', 'driver_wage' => 'Chi lương lái xe', 'partner_vehicle_cost' => 'Chi thuê xe', 'external_driver_cost' => 'Chi thuê lái', 'fuel_cost' => 'Chi phí dầu', 'repair_cost' => 'Chi sửa chữa', 'insurance_registration_cost' => 'Chi BH, ĐK', 'other_cost' => 'Chi khác', 'general_cost_allocated' => 'Chi phí chung PB', 'total_cost' => 'Tổng chi', 'profit' => 'Lợi nhuận'],
            default => [],
        };
        $money = ['opening_balance', 'fixed_trip_amount', 'tourism_trip_amount', 'incurred_amount', 'received_amount', 'closing_balance', 'partner_vehicle_cost', 'paid_amount', 'debit', 'credit', 'balance', 'revenue', 'driver_wage', 'external_driver_cost', 'external_vehicle_profit', 'driver_collected_amount', 'driver_remitted_amount', 'driver_expense_amount', 'company_collected_amount', 'remaining_receivable', 'responsibility_allowance', 'fixed_trip_wage', 'tourism_commission', 'gross_salary', 'advance_amount', 'net_salary', 'base_amount', 'amount', 'fuel_cost', 'repair_cost', 'insurance_registration_cost', 'other_cost', 'general_cost_allocated', 'total_cost', 'profit'];
        $rows = collect($report['rows'] ?? [])->values()->map(function (array $row, int $index) use ($columns, $money): array {
            $result = [];
            foreach ($columns as $key => $label) {
                $value = $key === 'stt' ? ($row['stt'] ?? $index + 1) : ($row[$key] ?? '');
                if (in_array($key, $money, true)) {
                    $value = number_format((float) $value, 2, ',', '.');
                } elseif ($key === 'date' && $value !== '') {
                    $value = Carbon::parse($value)->format('d/m/Y');
                } elseif (in_array($key, ['rate', 'driver_wage_rate'], true) && $value !== '') {
                    $value = number_format((float) $value * 100, 2, ',', '.').'%';
                }
                $result[$key] = (string) $value;
            }

            return $result;
        })->all();
        $totals = [];
        foreach ($report['totals'] ?? [] as $key => $value) {
            if (isset($columns[$key])) {
                $totals[$key] = number_format((float) $value, 2, ',', '.');
            }
        }

        return [...compact('columns', 'rows', 'totals'), 'legacy_trip_layout' => in_array($filename, ['business-trips', 'tourism-trips'], true)];
    }
}
