<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Exports\FinanceTableExport;
use App\Modules\Finance\Services\FinanceExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class FinanceExportController extends Controller
{
    public function __construct(private readonly FinanceExportService $service) {}

    public function receipts(Request $request): mixed
    {
        return $this->download($request, 'receipts');
    }

    public function vehicleExpenses(Request $request): mixed
    {
        return $this->download($request, 'vehicle-expenses');
    }

    public function generalExpenses(Request $request): mixed
    {
        return $this->download($request, 'general-expenses');
    }

    public function partnerPayments(Request $request): mixed
    {
        return $this->download($request, 'partner-payments');
    }

    private function download(Request $request, string $table): mixed
    {
        $format = $request->validate(['format' => ['required', 'in:pdf,xlsx']])['format'];
        $data = $this->service->table($table);
        $filename = $table.'-'.now()->format('Ymd');
        if ($format === 'xlsx') {
            return Excel::download(new FinanceTableExport($data['title'], $data['columns'], $data['rows']), $filename.'.xlsx', ExcelFormat::XLSX);
        }

        return Pdf::loadView('finance.export', $data)->setPaper('a4', 'landscape')->download($filename.'.pdf');
    }
}
