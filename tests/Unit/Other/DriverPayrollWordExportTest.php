<?php

namespace Tests\Unit\Other;

use App\Modules\Other\Exports\DriverPayrollWordExport;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class DriverPayrollWordExportTest extends TestCase
{
    public function test_it_creates_a_vietnamese_driver_payroll_word_document(): void
    {
        Carbon::setTestNow('2026-10-19 10:00:00');
        $path = app(DriverPayrollWordExport::class)->create([
            [
                'driver_name' => 'Nguyễn Văn A',
                'responsibility_allowance' => 500000,
                'fixed_trip_wage' => 5000000,
                'tourism_commission' => 1500000,
                'gross_salary' => 7000000,
                'driver_reimbursement' => 200000,
                'direct_customer_collection' => 1000000,
                'driver_remittance_amount' => 300000,
                'net_salary' => 6500000,
            ],
        ], '2026-10-01', '2026-10-31');

        try {
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($path) === true);
            $document = $archive->getFromName('word/document.xml');
            $footer = $archive->getFromName('word/footer1.xml');
            $archive->close();

            $this->assertIsString($document);
            $this->assertIsString($footer);
            $this->assertStringContainsString('BẢNG LƯƠNG TRẢ LÁI XE THÁNG 10/2026', $document);
            $this->assertStringContainsString('(Từ ngày 01/10/2026 đến ngày 31/10/2026)', $document);
            $this->assertStringContainsString('Lương trách nhiệm', $document);
            $this->assertStringContainsString('Lái xe chi', $document);
            $this->assertStringContainsString('Lái xe nộp', $document);
            $this->assertStringContainsString('Nguyễn Văn A', $document);
            $this->assertStringContainsString('7,000,000', $document);
            $this->assertStringContainsString('6,500,000', $document);
            $this->assertStringContainsString('Tổng cộng', $document);
            $this->assertStringContainsString('NGƯỜI LẬP', $document);
            $this->assertStringContainsString('KẾ TOÁN', $document);
            $this->assertStringContainsString('GIÁM ĐỐC', $document);
            $this->assertStringContainsString('PAGE', $footer);

            $xml = new DOMDocument;
            $this->assertTrue($xml->loadXML($document));
            $xpath = new DOMXPath($xml);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $tables = $xpath->query('/w:document/w:body/w:tbl');

            $this->assertNotFalse($tables);
            $this->assertCount(2, $tables);
            $this->assertGreaterThan(0, $xpath->query('.//w:tblBorders', $tables->item(0))->length);
            $this->assertSame(0, $xpath->query('.//w:tblBorders | .//w:tcBorders', $tables->item(1))->length);
        } finally {
            @unlink($path);
            Carbon::setTestNow();
        }
    }
}
