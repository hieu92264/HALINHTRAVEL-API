<?php

namespace Tests\Unit\Other;

use App\Modules\Other\Exports\CustomerDebtWordExport;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class CustomerDebtWordExportTest extends TestCase
{
    public function test_it_creates_a_vietnamese_customer_debt_summary_word_document(): void
    {
        Carbon::setTestNow('2026-10-19 10:00:00');
        $path = app(CustomerDebtWordExport::class)->create([
            ['name' => 'Công ty Cổ phần Minh Anh', 'opening_balance' => 1250000, 'incurred_amount' => 2500000, 'received_amount' => 250000, 'closing_balance' => 3500000],
            ['name' => 'Công ty An Phát', 'opening_balance' => 500000, 'incurred_amount' => 0, 'received_amount' => 100000, 'closing_balance' => 400000],
        ], '2026-10-01', '2026-10-31');

        try {
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($path) === true);
            $document = $archive->getFromName('word/document.xml');
            $footer = $archive->getFromName('word/footer1.xml');
            $archive->close();

            $this->assertIsString($document);
            $this->assertIsString($footer);
            $this->assertStringContainsString('BẢNG TỔNG HỢP CÔNG NỢ KHÁCH HÀNG', $document);
            $this->assertStringContainsString('(Từ ngày 01/10/2026 đến ngày 31/10/2026)', $document);
            $this->assertStringContainsString('Phát sinh trong kỳ', $document);
            $this->assertStringContainsString('Công ty Cổ phần Minh Anh', $document);
            $this->assertStringContainsString('1,750,000', $document);
            $this->assertStringContainsString('2,500,000', $document);
            $this->assertStringContainsString('350,000', $document);
            $this->assertStringContainsString('3,900,000', $document);
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

    public function test_it_creates_a_partner_debt_summary_with_the_partner_template_labels(): void
    {
        $path = app(CustomerDebtWordExport::class)->create(
            [['name' => 'Nhà xe Minh Anh', 'opening_balance' => 0, 'incurred_amount' => 2500000, 'paid_amount' => 500000, 'closing_balance' => 2000000]],
            '2026-10-01',
            '2026-10-31',
            'THỐNG KÊ CÔNG NỢ CÁC CHỦ XE',
            'Hãng vận tải/Chủ xe',
            'paid_amount',
        );

        try {
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($path) === true);
            $document = $archive->getFromName('word/document.xml');
            $archive->close();

            $this->assertIsString($document);
            $this->assertStringContainsString('THỐNG KÊ CÔNG NỢ CÁC CHỦ XE', $document);
            $this->assertStringContainsString('Hãng vận tải/Chủ xe', $document);
            $this->assertStringContainsString('Nhà xe Minh Anh', $document);
            $this->assertStringContainsString('2,500,000', $document);
            $this->assertStringContainsString('500,000', $document);
            $this->assertStringContainsString('2,000,000', $document);
        } finally {
            @unlink($path);
        }
    }
}
