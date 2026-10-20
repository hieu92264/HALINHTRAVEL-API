<?php

namespace App\Modules\Other\Exports;

use Carbon\Carbon;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class CustomerDebtDetailWordExport
{
    /** @param array{subject_name: string, opening_balance: string|float|int, closing_balance: string|float|int, rows: array<int, array<string, mixed>>} $detail */
    public function create(
        array $detail,
        string $fromDate,
        string $toDate,
        string $title = 'CHI TIẾT CÔNG NỢ KHÁCH HÀNG',
        string $subjectLabel = 'Khách hàng',
        string $temporaryPrefix = 'customer-debt-detail-',
    ): string {
        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(10.5);
        $section = $word->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 16838,
            'pageSizeH' => 11906,
            'marginTop' => 288,
            'marginBottom' => 288,
            'marginLeft' => 288,
            'marginRight' => 288,
            'footerHeight' => 288,
        ]);

        $this->addPageNumber($section);
        $this->addHeader($section);
        $section->addText($title, ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        $section->addText(
            sprintf('(Từ ngày %s đến ngày %s)', $this->reportDate($fromDate), $this->reportDate($toDate)),
            ['italic' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80],
        );
        $this->addInformation($section, $detail, $subjectLabel);
        $this->addDebtTable(
            $section,
            array_values(array_filter($detail['rows'], fn (array $row): bool => (int) ($row['stt'] ?? 0) > 0)),
            $detail['closing_balance'],
        );
        $this->addFooter($section);

        $temporary = tempnam(storage_path('app'), $temporaryPrefix);
        if ($temporary === false) {
            abort(500, 'Không thể tạo tệp Word báo cáo.');
        }
        @unlink($temporary);
        $path = $temporary.'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    /** @param Section $section */
    private function addHeader($section): void
    {
        // Do not set a zero-width border here. PHPWord still serializes that as a
        // table border and Microsoft Word renders it as a visible black line.
        $table = $section->addTable(['cellMargin' => 0, 'alignment' => JcTable::CENTER]);
        $table->addRow();
        $left = $table->addCell(7470, ['valign' => VerticalJc::CENTER]);
        $left->addText((string) config('reports.company_name'), ['bold' => true, 'size' => 11]);
        $right = $table->addCell(8100, ['valign' => VerticalJc::CENTER]);
        $right->addText('CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', ['bold' => true], ['alignment' => Jc::CENTER]);
        $right->addText('Độc lập - Tự do - Hạnh phúc', ['italic' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);
    }

    /** @param array{subject_name: string, opening_balance: string|float|int, closing_balance: string|float|int} $detail
     * @param  Section  $section
     */
    private function addInformation($section, array $detail, string $subjectLabel): void
    {
        $table = $section->addTable(['cellMargin' => 0]);
        $table->addRow();
        $table->addCell(11160)->addText($subjectLabel.': '.$detail['subject_name'], ['bold' => true]);
        $table->addCell(4410)->addText('Nợ đầu kỳ: '.$this->money($detail['opening_balance']), ['bold' => true], ['alignment' => Jc::RIGHT]);
        $section->addTextBreak(1);
    }

    /** @param array<int, array<string, mixed>> $rows
     * @param  Section  $section
     */
    private function addDebtTable($section, array $rows, mixed $closingBalance): void
    {
        $widths = [630, 1170, 3240, 1440, 990, 1080, 2610, 1440, 1440, 1530];
        $headers = ['STT', 'Ngày', 'Diễn giải/Hành trình', 'Biển số', 'Giờ đón', 'Giờ về', 'Ghi chú', 'Nợ phát sinh', 'Có phát sinh', 'Nợ cuối kỳ'];
        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => '000000',
            'cellMarginTop' => 70,
            'cellMarginBottom' => 70,
            'cellMarginLeft' => 55,
            'cellMarginRight' => 55,
            'alignment' => JcTable::CENTER,
        ]);
        $table->addRow(450, ['tblHeader' => true]);
        foreach ($headers as $index => $header) {
            $table->addCell($widths[$index], ['valign' => VerticalJc::CENTER])
                ->addText($header, ['bold' => true], ['alignment' => Jc::CENTER]);
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($rows as $row) {
            $debit = (float) ($row['debit'] ?? 0);
            $credit = (float) ($row['credit'] ?? 0);
            $totalDebit += $debit;
            $totalCredit += $credit;
            $table->addRow();
            $values = [
                empty($row['stt']) || (int) $row['stt'] === 0 ? '' : (string) $row['stt'],
                $this->transactionDate($row['date'] ?? null),
                trim(($row['type'] ?? '').' '.($row['journey'] ?? '')),
                $row['vehicle_plate'] ?? '',
                $row['pickup_time'] ?? '',
                $row['return_time'] ?? '',
                $row['note'] ?? '',
                $this->money($debit),
                $this->money($credit),
                '',
            ];
            foreach ($values as $index => $value) {
                $alignment = in_array($index, [0, 1, 3, 4, 5], true) ? Jc::CENTER : (in_array($index, [7, 8, 9], true) ? Jc::RIGHT : Jc::LEFT);
                $table->addCell($widths[$index], ['valign' => VerticalJc::CENTER])
                    ->addText((string) $value, [], ['alignment' => $alignment]);
            }
        }

        $table->addRow();
        $table->addCell($widths[0], ['gridSpan' => 7, 'valign' => VerticalJc::CENTER])
            ->addText('Tổng cộng', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell($widths[7])->addText($this->money($totalDebit), ['bold' => true], ['alignment' => Jc::RIGHT]);
        $table->addCell($widths[8])->addText($this->money($totalCredit), ['bold' => true], ['alignment' => Jc::RIGHT]);
        $table->addCell($widths[9])->addText($this->money($rows === [] ? $closingBalance : ($rows[array_key_last($rows)]['balance'] ?? $closingBalance)), ['bold' => true], ['alignment' => Jc::RIGHT]);
    }

    /** @param Section $section */
    private function addFooter($section): void
    {
        $now = now();
        $section->addText(
            sprintf('%s, ngày %d tháng %d năm %d.', config('reports.city'), $now->day, $now->month, $now->year),
            ['italic' => true],
            ['alignment' => Jc::RIGHT, 'spaceBefore' => 120, 'spaceAfter' => 240],
        );
        $table = $section->addTable(['cellMargin' => 0]);
        $table->addRow();
        $table->addCell(6480, ['valign' => VerticalJc::CENTER])
            ->addText('NGƯỜI LẬP', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(4680, ['valign' => VerticalJc::CENTER])
            ->addText('KẾ TOÁN', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(4410, ['valign' => VerticalJc::CENTER])
            ->addText('GIÁM ĐỐC', ['bold' => true], ['alignment' => Jc::CENTER]);
    }

    /** @param Section $section */
    private function addPageNumber($section): void
    {
        $section->addFooter()->addPreserveText('{PAGE}', [], ['alignment' => Jc::RIGHT]);
    }

    private function reportDate(mixed $value): string
    {
        return blank($value) ? '' : Carbon::parse($value)->format('d/m/Y');
    }

    private function transactionDate(mixed $value): string
    {
        return blank($value) ? '' : Carbon::parse($value)->format('d/m/y');
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }
}
