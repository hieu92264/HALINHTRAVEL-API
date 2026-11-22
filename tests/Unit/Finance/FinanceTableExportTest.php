<?php

namespace Tests\Unit\Finance;

use App\Modules\Finance\Exports\FinanceTableExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class FinanceTableExportTest extends TestCase
{
    public function test_it_places_the_export_date_above_the_preparer_signature(): void
    {
        $contents = Excel::raw(new FinanceTableExport(
            'PHIẾU THU KHÁCH HÀNG',
            'Hải Phòng, ngày 22 tháng 11 năm 2026',
            ['receipt_no' => 'Số phiếu', 'amount' => 'Số tiền'],
            [['receipt_no' => 'PT-001', 'amount' => '100.000']],
        ), ExcelFormat::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'finance-excel-');
        file_put_contents($path, $contents);

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();

            $this->assertSame('Hải Phòng, ngày 22 tháng 11 năm 2026', $sheet->getCell('A7')->getValue());
            $this->assertSame('NGƯỜI LẬP', $sheet->getCell('A8')->getValue());
            $this->assertSame('right', $sheet->getStyle('A7')->getAlignment()->getHorizontal());
            $this->assertTrue($sheet->getStyle('A7')->getFont()->getItalic());
        } finally {
            @unlink($path);
        }
    }
}
