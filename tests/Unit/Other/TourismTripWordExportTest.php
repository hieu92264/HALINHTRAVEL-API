<?php

namespace Tests\Unit\Other;

use App\Modules\Other\Exports\TourismTripWordExport;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class TourismTripWordExportTest extends TestCase
{
    public function test_it_creates_a_vietnamese_tourism_trip_word_document(): void
    {
        Carbon::setTestNow('2026-10-19 10:00:00');
        $path = app(TourismTripWordExport::class)->create([
            [
                'date' => '2026-10-05',
                'vehicle_plate' => '15B-123.45',
                'journey' => 'Hà Nội - Hạ Long',
                'driver_name' => 'Nguyễn Văn A',
                'revenue' => 10000000,
                'driver_wage' => 1500000,
                'external_driver_cost' => 500000,
                'partner_vehicle_cost' => 2000000,
                'driver_collected_amount' => 3000000,
                'driver_remitted_amount' => 1000000,
                'company_collected_amount' => 6000000,
                'remaining_receivable' => 1000000,
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
            $this->assertStringContainsString('BÁO CÁO XE CHẠY DU LỊCH', $document);
            $this->assertStringContainsString('(Từ ngày 01/10/2026 đến ngày 31/10/2026)', $document);
            $this->assertStringContainsString('Tiền thuê lái', $document);
            $this->assertStringContainsString('Tiền thuê xe', $document);
            $this->assertStringContainsString('Lái xe thu', $document);
            $this->assertStringContainsString('Lái xe nộp', $document);
            $this->assertStringContainsString('Công ty thu', $document);
            $this->assertStringContainsString('15', $document);
            $this->assertStringContainsString('10,000,000', $document);
            $this->assertStringContainsString('1,000,000', $document);
            $this->assertStringContainsString('Tổng cộng', $document);
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
        } finally {
            @unlink($path);
            Carbon::setTestNow();
        }
    }
}
