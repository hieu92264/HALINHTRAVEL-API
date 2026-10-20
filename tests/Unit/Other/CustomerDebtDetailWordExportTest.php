<?php

namespace Tests\Unit\Other;

use App\Modules\Other\Exports\CustomerDebtDetailWordExport;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class CustomerDebtDetailWordExportTest extends TestCase
{
    public function test_it_creates_a_vietnamese_customer_debt_detail_word_document(): void
    {
        Carbon::setTestNow('2026-03-03 15:20:00');
        $path = app(CustomerDebtDetailWordExport::class)->create([
            'subject_name' => 'Công ty Cổ phần Minh Anh',
            'opening_balance' => 1250000,
            'closing_balance' => 3500000,
            'rows' => [
                [
                    'stt' => 0,
                    'date' => '2026-10-01',
                    'type' => 'Nợ đầu kỳ',
                    'journey' => '',
                    'vehicle_plate' => '',
                    'pickup_time' => '',
                    'return_time' => '',
                    'note' => '',
                    'debit' => 0,
                    'credit' => 0,
                    'balance' => 1250000,
                ],
                [
                    'stt' => 1,
                    'date' => '2026-10-05',
                    'type' => 'Lệnh điều xe LDX001',
                    'journey' => 'Hà Nội - Hải Phòng',
                    'vehicle_plate' => '15B-123.45',
                    'pickup_time' => '06:00',
                    'return_time' => '18:00',
                    'note' => 'Chuyến công nhân',
                    'debit' => 2500000,
                    'credit' => 250000,
                    'balance' => 3500000,
                ],
            ],
        ], '2026-10-01', '2026-10-31');

        try {
            $this->assertFileExists($path);
            $this->assertSame('docx', pathinfo($path, PATHINFO_EXTENSION));

            $archive = new ZipArchive;
            $this->assertTrue($archive->open($path) === true);
            $document = $archive->getFromName('word/document.xml');
            $footer = $archive->getFromName('word/footer1.xml');
            $archive->close();

            $this->assertIsString($document);
            $this->assertIsString($footer);
            $this->assertStringContainsString('CHI TIẾT CÔNG NỢ KHÁCH HÀNG', $document);
            $this->assertStringContainsString('(Từ ngày 01/10/2026 đến ngày 31/10/2026)', $document);
            $this->assertStringContainsString('Công ty Cổ phần Minh Anh', $document);
            $this->assertStringContainsString('Nợ đầu kỳ: 1,250,000', $document);
            $this->assertStringContainsString('Hà Nội - Hải Phòng', $document);
            $this->assertStringContainsString('05/10/26', $document);
            $this->assertStringContainsString('Tổng cộng', $document);
            $this->assertStringContainsString('2,500,000', $document);
            $this->assertStringContainsString('250,000', $document);
            $this->assertSame(1, substr_count($document, 'Nợ đầu kỳ'));
            $this->assertSame(1, substr_count($document, '3,500,000'));
            $this->assertStringContainsString('w:tblHeader', $document);
            $this->assertStringContainsString('PAGE', $footer);
            $this->assertStringContainsString('Hải Phòng, ngày 3 tháng 3 năm 2026.', $document);
            $this->assertStringContainsString('NGƯỜI LẬP', $document);
            $this->assertStringContainsString('KẾ TOÁN', $document);
            $this->assertStringContainsString('GIÁM ĐỐC', $document);

            $xml = new DOMDocument;
            $this->assertTrue($xml->loadXML($document));
            $xpath = new DOMXPath($xml);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $tables = $xpath->query('/w:document/w:body/w:tbl');

            $this->assertNotFalse($tables);
            $this->assertCount(4, $tables);
            foreach ([0, 1, 3] as $tableIndex) {
                $this->assertSame(0, $xpath->query('.//w:tblBorders | .//w:tcBorders', $tables->item($tableIndex))->length);
            }
            $this->assertGreaterThan(0, $xpath->query('.//w:tblBorders', $tables->item(2))->length);
        } finally {
            @unlink($path);
            Carbon::setTestNow();
        }
    }

    public function test_it_creates_a_partner_debt_detail_with_the_partner_template_labels(): void
    {
        $path = app(CustomerDebtDetailWordExport::class)->create(
            [
                'subject_name' => 'Nhà xe Minh Anh',
                'opening_balance' => 1000000,
                'closing_balance' => 3250000,
                'rows' => [
                    [
                        'stt' => 1,
                        'date' => '2026-10-05',
                        'type' => 'Lệnh điều xe LDX001',
                        'journey' => 'Hà Nội - Hải Phòng',
                        'vehicle_plate' => '15B-123.45',
                        'pickup_time' => '06:00',
                        'return_time' => '18:00',
                        'note' => 'Chuyến công nhân',
                        'debit' => 2500000,
                        'credit' => 250000,
                        'balance' => 3250000,
                    ],
                ],
            ],
            '2026-10-01',
            '2026-10-31',
            'CHI TIẾT CÔNG NỢ CHỦ XE',
            'Chủ xe',
            'partner-debt-detail-',
        );

        try {
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($path) === true);
            $document = $archive->getFromName('word/document.xml');
            $archive->close();

            $this->assertIsString($document);
            $this->assertStringContainsString('CHI TIẾT CÔNG NỢ CHỦ XE', $document);
            $this->assertStringContainsString('Chủ xe: Nhà xe Minh Anh', $document);
            $this->assertStringContainsString('05/10/26', $document);
            $this->assertStringContainsString('Tổng cộng', $document);
            $this->assertStringContainsString('3,250,000', $document);
        } finally {
            @unlink($path);
        }
    }
}
