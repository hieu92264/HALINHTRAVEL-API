<?php

namespace Tests\Unit\Other;

use App\Modules\Other\Exports\VehicleProfitWordExport;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class VehicleProfitWordExportTest extends TestCase
{
    public function test_it_creates_a_vietnamese_vehicle_profit_word_document(): void
    {
        Carbon::setTestNow('2026-10-19 10:00:00');
        $path = app(VehicleProfitWordExport::class)->create([
            [
                'vehicle_plate' => '15B-123.45',
                'vehicle_type' => 'Xe 29 chỗ',
                'revenue' => 10000000,
                'driver_wage' => 1000000,
                'partner_vehicle_cost' => 2000000,
                'external_driver_cost' => 500000,
                'fuel_cost' => 1500000,
                'repair_cost' => 250000,
                'insurance_registration_cost' => 100000,
                'other_cost' => 50000,
                'general_cost_allocated' => 300000,
                'total_cost' => 5700000,
                'profit' => 4300000,
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
            $this->assertStringContainsString('BẢNG LỢI NHUẬN XE THÁNG 10/2026', $document);
            $this->assertStringContainsString('(Từ ngày 01/10/2026 đến ngày 31/10/2026)', $document);
            $this->assertStringContainsString('Chi thuê xe', $document);
            $this->assertStringContainsString('Chi thuê lái', $document);
            $this->assertStringContainsString('15B-123.45', $document);
            $this->assertStringContainsString('10,000,000', $document);
            $this->assertStringContainsString('5,700,000', $document);
            $this->assertStringContainsString('4,300,000', $document);
            $this->assertStringContainsString('Tổng cộng', $document);
            $this->assertStringContainsString('Hải Phòng, ngày 19 tháng 10 năm 2026.', $document);
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
