<?php

namespace Tests\Unit\Other;

use App\Modules\Other\Exports\DriverPayrollDetailWordExport;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class DriverPayrollDetailWordExportTest extends TestCase
{
    public function test_it_creates_a_vietnamese_driver_payroll_detail_word_document(): void
    {
        Carbon::setTestNow('2026-10-19 10:00:00');
        $path = app(DriverPayrollDetailWordExport::class)->create([
            [
                'driver_name' => 'Nguyễn Văn A',
                'calculation_type' => 'tourism_commission',
                'date' => '2026-10-05',
                'order_no' => 'LDX20260001',
                'journey' => 'Hải Phòng - Hạ Long',
                'base_amount' => 5000000,
                'rate' => 0.15,
                'amount' => 750000,
                'description' => 'Hoàn thành chuyến du lịch',
            ],
            [
                'driver_name' => 'Nguyễn Văn A',
                'calculation_type' => 'driver_cash_remittance',
                'date' => '2026-10-06',
                'remittance_no' => 'NTX20260001',
                'base_amount' => 500000,
                'rate' => null,
                'amount' => -500000,
                'description' => 'Nộp tiền khách đã thu',
            ],
        ], ['gross_salary' => 750000, 'net_salary' => 250000], '2026-10-01', '2026-10-31');

        try {
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($path) === true);
            $document = $archive->getFromName('word/document.xml');
            $footer = $archive->getFromName('word/footer1.xml');
            $archive->close();

            $this->assertIsString($document);
            $this->assertIsString($footer);
            $this->assertStringContainsString('CHI TIẾT LƯƠNG LÁI XE', $document);
            $this->assertStringContainsString('(Từ ngày 01/10/2026 đến ngày 31/10/2026)', $document);
            $this->assertStringContainsString('Nguyễn Văn A', $document);
            $this->assertStringContainsString('Lương chuyến du lịch', $document);
            $this->assertStringContainsString('Lái xe nộp tiền', $document);
            $this->assertStringContainsString('LDX20260001', $document);
            $this->assertStringContainsString('NTX20260001', $document);
            $this->assertStringContainsString('05/10/26', $document);
            $this->assertStringContainsString('15.00%', $document);
            $this->assertStringContainsString('Tổng phát sinh chi tiết', $document);
            $this->assertStringContainsString('Tổng lương: 750,000 đồng', $document);
            $this->assertStringContainsString('Thực lĩnh: 250,000 đồng', $document);
            $this->assertStringContainsString('NGƯỜI LẬP', $document);
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
            $this->assertGreaterThan(0, $xpath->query('.//w:trPr/w:tblHeader', $tables->item(0))->length);
        } finally {
            @unlink($path);
            Carbon::setTestNow();
        }
    }
}
