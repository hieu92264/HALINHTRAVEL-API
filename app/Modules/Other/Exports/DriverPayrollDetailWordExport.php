<?php

namespace App\Modules\Other\Exports;

use Carbon\Carbon;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class DriverPayrollDetailWordExport
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $totals
     */
    public function create(array $rows, array $totals, string $fromDate, string $toDate): string
    {
        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(9.5);
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
        $this->addHeader($section, $fromDate, $toDate);
        $this->addDetailTable($section, $rows, $totals);
        $this->addFooter($section);

        $temporary = tempnam(storage_path('app'), 'driver-payroll-detail-');
        if ($temporary === false) {
            abort(500, 'Không thể tạo tệp Word báo cáo.');
        }

        @unlink($temporary);
        $path = $temporary.'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    /** @param Section $section */
    private function addHeader($section, string $fromDate, string $toDate): void
    {
        $section->addText((string) config('reports.company_name'), ['bold' => true, 'size' => 11], ['spaceAfter' => 180]);
        $section->addText('CHI TIẾT LƯƠNG LÁI XE', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        $section->addText(
            sprintf('(Từ ngày %s đến ngày %s)', Carbon::parse($fromDate)->format('d/m/Y'), Carbon::parse($toDate)->format('d/m/Y')),
            ['italic' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 180],
        );
    }

    /**
     * @param  Section  $section
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $totals
     */
    private function addDetailTable($section, array $rows, array $totals): void
    {
        $widths = [550, 1700, 1100, 1600, 1700, 4750, 1400, 900, 1600];
        $headers = ['STT', 'Lái xe', 'Ngày', 'Loại khoản', 'Lệnh/chứng từ', 'Hành trình/Diễn giải', 'Cơ sở tính', 'Tỷ lệ', 'Số tiền'];
        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => '000000',
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 40,
            'cellMarginRight' => 40,
            'alignment' => JcTable::CENTER,
        ]);
        $table->addRow(640, ['tblHeader' => true]);
        foreach ($headers as $index => $header) {
            $table->addCell($widths[$index], ['valign' => VerticalJc::CENTER])
                ->addText($header, ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER]);
        }

        $detailTotal = 0.0;
        foreach (array_values($rows) as $index => $row) {
            $amount = (float) ($row['amount'] ?? 0);
            $detailTotal += $amount;
            $values = [
                $index + 1,
                $row['driver_name'] ?? '',
                $this->date($row['date'] ?? null),
                $this->calculationLabel((string) ($row['calculation_type'] ?? '')),
                $this->documentNo($row),
                $this->description($row),
                $this->money((float) ($row['base_amount'] ?? 0)),
                $this->rate($row['rate'] ?? null),
                $this->money($amount),
            ];
            $table->addRow();
            foreach ($values as $column => $value) {
                $alignment = in_array($column, [0, 2, 7], true) ? Jc::CENTER : (in_array($column, [6, 8], true) ? Jc::RIGHT : Jc::LEFT);
                $style = $column === 8 && $amount < 0 ? ['color' => 'C00000'] : [];
                $table->addCell($widths[$column], ['valign' => VerticalJc::CENTER])
                    ->addText((string) $value, $style, ['alignment' => $alignment]);
            }
        }

        $table->addRow();
        $table->addCell($widths[0], ['gridSpan' => 8, 'valign' => VerticalJc::CENTER])
            ->addText('Tổng phát sinh chi tiết', ['bold' => true], ['alignment' => Jc::RIGHT]);
        $table->addCell($widths[8], ['valign' => VerticalJc::CENTER])
            ->addText($this->money($detailTotal), ['bold' => true], ['alignment' => Jc::RIGHT]);

        $section->addText(
            sprintf('Tổng lương: %s đồng     Thực lĩnh: %s đồng', $this->money((float) ($totals['gross_salary'] ?? 0)), $this->money((float) ($totals['net_salary'] ?? 0))),
            ['bold' => true],
            ['alignment' => Jc::RIGHT, 'spaceBefore' => 120, 'spaceAfter' => 120],
        );
    }

    /** @param array<string, mixed> $row */
    private function documentNo(array $row): string
    {
        return (string) ($row['order_no'] ?? $row['receipt_no'] ?? $row['expense_no'] ?? $row['remittance_no'] ?? '');
    }

    /** @param array<string, mixed> $row */
    private function description(array $row): string
    {
        return implode(' - ', array_filter([
            $row['journey'] ?? null,
            $row['description'] ?? null,
        ], fn (mixed $value): bool => filled($value)));
    }

    /** @param Section $section */
    private function addFooter($section): void
    {
        $now = now();
        $section->addText(
            sprintf('%s, ngày %d tháng %d năm %d.', config('reports.city'), $now->day, $now->month, $now->year),
            ['italic' => true],
            ['alignment' => Jc::RIGHT, 'spaceBefore' => 60, 'spaceAfter' => 240],
        );

        $table = $section->addTable(['cellMargin' => 0]);
        $table->addRow();
        foreach (['NGƯỜI LẬP', 'KẾ TOÁN', 'GIÁM ĐỐC'] as $label) {
            $table->addCell(5000, ['valign' => VerticalJc::CENTER])
                ->addText($label, ['bold' => true], ['alignment' => Jc::CENTER]);
        }
    }

    /** @param Section $section */
    private function addPageNumber($section): void
    {
        $section->addFooter()->addPreserveText('{PAGE}', [], ['alignment' => Jc::RIGHT]);
    }

    private function calculationLabel(string $type): string
    {
        return match ($type) {
            'fixed_trip' => 'Lương chuyến công nhân',
            'tourism_commission' => 'Lương chuyến du lịch',
            'direct_customer_collection' => 'Lái xe thu tiền',
            'driver_reimbursement' => 'Lái xe tự chi',
            'driver_cash_remittance' => 'Lái xe nộp tiền',
            'allowance' => 'Lương trách nhiệm',
            default => 'Khác',
        };
    }

    private function date(mixed $date): string
    {
        return filled($date) ? Carbon::parse($date)->format('d/m/y') : '';
    }

    private function rate(mixed $rate): string
    {
        if ($rate === null || $rate === '') {
            return '';
        }

        return number_format((float) $rate * 100, 2, '.', '').'%';
    }

    private function money(float $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
