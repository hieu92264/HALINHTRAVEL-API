<?php

namespace Tests\Unit\Other;

use App\Modules\Other\Exports\ReportExcelExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportExcelExportTest extends TestCase
{
    public function test_it_creates_a_report_workbook_with_headers_totals_and_signatures(): void
    {
        $contents = Excel::raw(new ReportExcelExport(
            'BẢNG LƯƠNG LÁI XE',
            'Từ ngày 01/11/2026 đến ngày 08/11/2026',
            'Nguyễn Văn Hùng',
            ['stt' => 'STT', 'driver_name' => 'Lái xe', 'rate' => 'Tỷ lệ %', 'amount' => 'Số tiền'],
            [['stt' => '1', 'driver_name' => 'Nguyễn Văn Hùng', 'rate' => '15%', 'amount' => '300.000,00']],
            ['amount' => '300.000,00'],
        ), ExcelFormat::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'report-excel-');
        file_put_contents($path, $contents);

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();

            $this->assertSame('BẢNG LƯƠNG LÁI XE', $sheet->getCell('A2')->getValue());
            $this->assertSame('Đối tượng: Nguyễn Văn Hùng', $sheet->getCell('A4')->getValue());
            $this->assertSame('Tỷ lệ %', $sheet->getCell('C5')->getValue());
            $this->assertSame('15%', $sheet->getCell('C6')->getValue());
            $this->assertSame('Tổng cộng', $sheet->getCell('A7')->getValue());
            $this->assertStringContainsString('GIÁM ĐỐC', (string) $sheet->getCell('A11')->getValue());
            $this->assertStringContainsString('KẾ TOÁN', (string) $sheet->getCell('B11')->getValue());
            $this->assertStringContainsString('NGƯỜI LẬP', (string) $sheet->getCell('C11')->getValue());
        } finally {
            @unlink($path);
        }
    }
}
