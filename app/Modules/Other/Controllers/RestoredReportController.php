<?php

namespace App\Modules\Other\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Other\Exports\CustomerDebtDetailWordExport;
use App\Modules\Other\Exports\CustomerDebtWordExport;
use App\Modules\Other\Exports\DriverPayrollDetailWordExport;
use App\Modules\Other\Exports\DriverPayrollWordExport;
use App\Modules\Other\Exports\FixedTripWordExport;
use App\Modules\Other\Exports\TourismTripWordExport;
use App\Modules\Other\Exports\VehicleProfitWordExport;
use App\Modules\Other\Requests\ReportRequest;
use App\Modules\Other\Services\RestoredReportService;
use HieuDev92264\LaravelModules\Traits\ApiResponse;

class RestoredReportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RestoredReportService $service) {}

    public function businessTrips(ReportRequest $request): mixed
    {
        return $this->respond('businessTrips', $request, 'business-trips', FixedTripWordExport::class);
    }

    public function tourismTrips(ReportRequest $request): mixed
    {
        return $this->respond('tourismTrips', $request, 'tourism-trips', TourismTripWordExport::class);
    }

    public function customerDebts(ReportRequest $request): mixed
    {
        return $this->respond('customerDebts', $request, 'customer-debts', CustomerDebtWordExport::class);
    }

    public function partnerDebts(ReportRequest $request): mixed
    {
        return $this->respond('partnerDebts', $request, 'partner-debts', CustomerDebtWordExport::class);
    }

    public function driverPayroll(ReportRequest $request): mixed
    {
        return $this->respond('driverPayroll', $request, 'driver-payroll', DriverPayrollWordExport::class);
    }

    public function vehicleProfit(ReportRequest $request): mixed
    {
        return $this->respond('vehicleProfit', $request, 'vehicle-profit', VehicleProfitWordExport::class);
    }

    public function customerDebtDetails(ReportRequest $request): mixed
    {
        return $this->respond('customerDebtDetails', $request, 'customer-debt-details', CustomerDebtDetailWordExport::class);
    }

    public function partnerDebtDetails(ReportRequest $request): mixed
    {
        return $this->respond('partnerDebtDetails', $request, 'partner-debt-details', CustomerDebtDetailWordExport::class);
    }

    public function driverPayrollDetails(ReportRequest $request): mixed
    {
        return $this->respond('driverPayrollDetails', $request, 'driver-payroll-details', DriverPayrollDetailWordExport::class);
    }

    private function respond(string $method, ReportRequest $request, string $filename, string $exporter): mixed
    {
        $filters = $request->filters();
        $report = $this->service->{$method}($filters);
        if ($request->validated('format') !== 'docx') {
            return $this->success($report, 'Lấy báo cáo thành công.');
        }
        $path = $this->word($exporter, $report, $filters, $filename);

        return response()->download($path, $filename.'-'.$filters['from_date'].'-'.$filters['to_date'].'.docx')->deleteFileAfterSend(true);
    }

    private function word(string $exporter, array $report, array $filters, string $filename): string
    {
        $export = app($exporter);

        return match ($filename) {
            'customer-debts' => $export->create($report['rows'], $filters['from_date'], $filters['to_date']),
            'partner-debts' => $export->create($report['rows'], $filters['from_date'], $filters['to_date'], 'BẢNG TỔNG HỢP CÔNG NỢ CHỦ XE', 'Chủ xe', 'paid_amount'),
            'customer-debt-details' => $export->create(['subject_name' => $report['subject_name'], 'opening_balance' => $report['opening_balance'], 'closing_balance' => $report['closing_balance'], 'rows' => $report['rows']], $filters['from_date'], $filters['to_date']),
            'partner-debt-details' => $export->create(['subject_name' => $report['subject_name'], 'opening_balance' => $report['opening_balance'], 'closing_balance' => $report['closing_balance'], 'rows' => $report['rows']], $filters['from_date'], $filters['to_date'], 'CHI TIẾT CÔNG NỢ CHỦ XE', 'Chủ xe', 'partner-debt-detail-'),
            'driver-payroll-details' => $export->create($report['rows'], $report, $filters['from_date'], $filters['to_date']),
            default => $export->create($report['rows'], $filters['from_date'], $filters['to_date']),
        };
    }
}
