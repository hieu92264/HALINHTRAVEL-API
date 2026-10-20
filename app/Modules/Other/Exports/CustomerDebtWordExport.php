<?php

namespace App\Modules\Other\Exports;

use Carbon\Carbon;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class CustomerDebtWordExport
{
    /** @param array<int, array{name?: string, opening_balance?: string|float|int, incurred_amount?: string|float|int, received_amount?: string|float|int, closing_balance?: string|float|int}> $rows */
    public function create(
        array $rows,
        string $fromDate,
        string $toDate,
        string $title = 'BẢNG TỔNG HỢP CÔNG NỢ KHÁCH HÀNG',
        string $subjectColumn = 'Khách hàng',
        string $creditField = 'received_amount',
    ): string {
        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(10.5);
        $section = $word->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginTop' => 288,
            'marginBottom' => 288,
            'marginLeft' => 288,
            'marginRight' => 288,
            'footerHeight' => 288,
        ]);

        $this->addPageNumber($section);
        $this->addHeader($section, $fromDate, $toDate, $title);
        $this->addDebtTable($section, $rows, $subjectColumn, $creditField);
        $this->addFooter($section);

        $temporary = tempnam(storage_path('app'), 'customer-debts-');
        if ($temporary === false) {
            abort(500, 'Không thể tạo tệp Word báo cáo.');
        }
        @unlink($temporary);
        $path = $temporary.'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    /** @param Section $section */
    private function addHeader($section, string $fromDate, string $toDate, string $title): void
    {
        $section->addText((string) config('reports.company_name'), ['bold' => true, 'size' => 11], ['spaceAfter' => 180]);
        $section->addText($title, ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        $section->addText(
            sprintf('(Từ ngày %s đến ngày %s)', $this->reportDate($fromDate), $this->reportDate($toDate)),
            ['italic' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 180],
        );
    }

    /** @param array<int, array{name?: string, opening_balance?: string|float|int, incurred_amount?: string|float|int, received_amount?: string|float|int, closing_balance?: string|float|int}> $rows
     * @param  Section  $section
     */
    private function addDebtTable($section, array $rows, string $subjectColumn, string $creditField): void
    {
        $widths = [600, 4440, 1440, 1440, 1440, 1440];
        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => '000000',
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 50,
            'cellMarginRight' => 50,
            'alignment' => JcTable::CENTER,
        ]);

        $table->addRow(360, ['tblHeader' => true]);
        $table->addCell($widths[0], ['vMerge' => 'restart', 'valign' => VerticalJc::CENTER])->addText('STT', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell($widths[1], ['vMerge' => 'restart', 'valign' => VerticalJc::CENTER])->addText($subjectColumn, ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell($widths[2], ['vMerge' => 'restart', 'valign' => VerticalJc::CENTER])->addText('Nợ đầu', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell($widths[3], ['gridSpan' => 2, 'valign' => VerticalJc::CENTER])->addText('Phát sinh trong kỳ', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell($widths[5], ['vMerge' => 'restart', 'valign' => VerticalJc::CENTER])->addText('Nợ cuối kỳ', ['bold' => true], ['alignment' => Jc::CENTER]);

        $table->addRow(360, ['tblHeader' => true]);
        $table->addCell($widths[0], ['vMerge' => 'continue']);
        $table->addCell($widths[1], ['vMerge' => 'continue']);
        $table->addCell($widths[2], ['vMerge' => 'continue']);
        $table->addCell($widths[3], ['valign' => VerticalJc::CENTER])->addText('Nợ', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell($widths[4], ['valign' => VerticalJc::CENTER])->addText('Có', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell($widths[5], ['vMerge' => 'continue']);

        $totalOpening = 0.0;
        $totalIncurred = 0.0;
        $totalReceived = 0.0;
        $totalClosing = 0.0;
        foreach (array_values($rows) as $index => $row) {
            $opening = (float) ($row['opening_balance'] ?? 0);
            $incurred = (float) ($row['incurred_amount'] ?? 0);
            $received = (float) ($row[$creditField] ?? 0);
            $closing = (float) ($row['closing_balance'] ?? 0);
            $totalOpening += $opening;
            $totalIncurred += $incurred;
            $totalReceived += $received;
            $totalClosing += $closing;

            $table->addRow();
            $values = [$index + 1, $row['name'] ?? '', $this->money($opening), $this->money($incurred), $this->money($received), $this->money($closing)];
            foreach ($values as $column => $value) {
                $alignment = $column === 1 ? Jc::LEFT : ($column === 0 ? Jc::CENTER : Jc::RIGHT);
                $table->addCell($widths[$column], ['valign' => VerticalJc::CENTER])
                    ->addText((string) $value, [], ['alignment' => $alignment]);
            }
        }

        $table->addRow();
        $table->addCell($widths[0], ['gridSpan' => 2, 'valign' => VerticalJc::CENTER])
            ->addText('Tổng cộng', ['bold' => true], ['alignment' => Jc::CENTER]);
        foreach ([$totalOpening, $totalIncurred, $totalReceived, $totalClosing] as $total) {
            $table->addCell($widths[2], ['valign' => VerticalJc::CENTER])
                ->addText($this->money($total), ['bold' => true], ['alignment' => Jc::RIGHT]);
        }
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
        $table->addCell(3120, ['valign' => VerticalJc::CENTER])
            ->addText('NGƯỜI LẬP', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(3480, ['valign' => VerticalJc::CENTER])
            ->addText('KẾ TOÁN', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(4200, ['valign' => VerticalJc::CENTER])
            ->addText('GIÁM ĐỐC', ['bold' => true], ['alignment' => Jc::CENTER]);
    }

    /** @param Section $section */
    private function addPageNumber($section): void
    {
        $section->addFooter()->addPreserveText('{PAGE}', [], ['alignment' => Jc::RIGHT]);
    }

    private function reportDate(string $value): string
    {
        return Carbon::parse($value)->format('d/m/Y');
    }

    private function money(float $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
