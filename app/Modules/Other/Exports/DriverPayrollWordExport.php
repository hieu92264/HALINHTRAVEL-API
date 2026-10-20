<?php

namespace App\Modules\Other\Exports;

use Carbon\Carbon;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class DriverPayrollWordExport
{
    /** @param array<int, array<string, mixed>> $rows */
    public function create(array $rows, string $fromDate, string $toDate): string
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
        $this->addPayrollTable($section, $rows);
        $this->addFooter($section);

        $temporary = tempnam(storage_path('app'), 'driver-payroll-');
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
        $month = Carbon::parse($fromDate)->format('n/Y');
        $section->addText("BẢNG LƯƠNG TRẢ LÁI XE THÁNG {$month}", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        $section->addText(
            sprintf('(Từ ngày %s đến ngày %s)', Carbon::parse($fromDate)->format('d/m/Y'), Carbon::parse($toDate)->format('d/m/Y')),
            ['italic' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 180],
        );
    }

    /** @param array<int, array<string, mixed>> $rows
     * @param  Section  $section
     */
    private function addPayrollTable($section, array $rows): void
    {
        $widths = [520, 2260, 1450, 1390, 1500, 1450, 1480, 1350, 1340, 1680, 1300];
        $headers = [
            'STT', 'Họ và tên', 'Lương trách nhiệm', 'Lương lái công nhân', 'Lương lái du lịch',
            'Tổng cộng', 'Lái xe chi', 'Lái xe thu', 'Lái xe nộp', 'Còn lĩnh', 'Ký nhận',
        ];
        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => '000000',
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 40,
            'cellMarginRight' => 40,
            'alignment' => JcTable::CENTER,
        ]);
        $table->addRow(620, ['tblHeader' => true]);
        foreach ($headers as $index => $header) {
            $table->addCell($widths[$index], ['valign' => VerticalJc::CENTER])
                ->addText($header, ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER]);
        }

        $fields = [
            'responsibility_allowance', 'fixed_trip_wage', 'tourism_commission', 'gross_salary',
            'driver_reimbursement', 'direct_customer_collection', 'driver_remittance_amount', 'net_salary',
        ];
        $totals = array_fill_keys($fields, 0.0);
        foreach (array_values($rows) as $index => $row) {
            foreach ($fields as $field) {
                $totals[$field] += (float) ($row[$field] ?? 0);
            }
            $values = [
                $index + 1,
                $row['driver_name'] ?? '',
                ...array_map(fn (string $field): string => $this->money((float) ($row[$field] ?? 0)), $fields),
                '',
            ];
            $table->addRow();
            foreach ($values as $column => $value) {
                $alignment = $column === 0 ? Jc::CENTER : ($column === 1 ? Jc::LEFT : (in_array($column, range(2, 9), true) ? Jc::RIGHT : Jc::CENTER));
                $table->addCell($widths[$column], ['valign' => VerticalJc::CENTER])
                    ->addText((string) $value, [], ['alignment' => $alignment]);
            }
        }

        $table->addRow();
        $table->addCell($widths[0], ['gridSpan' => 2, 'valign' => VerticalJc::CENTER])
            ->addText('Tổng cộng', ['bold' => true], ['alignment' => Jc::CENTER]);
        foreach ($fields as $field) {
            $table->addCell($widths[2], ['valign' => VerticalJc::CENTER])
                ->addText($this->money($totals[$field]), ['bold' => true], ['alignment' => Jc::RIGHT]);
        }
        $table->addCell($widths[10]);
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
        $table->addCell(5000, ['valign' => VerticalJc::CENTER])
            ->addText('NGƯỜI LẬP', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(5000, ['valign' => VerticalJc::CENTER])
            ->addText('KẾ TOÁN', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(5000, ['valign' => VerticalJc::CENTER])
            ->addText('GIÁM ĐỐC', ['bold' => true], ['alignment' => Jc::CENTER]);
    }

    /** @param Section $section */
    private function addPageNumber($section): void
    {
        $section->addFooter()->addPreserveText('{PAGE}', [], ['alignment' => Jc::RIGHT]);
    }

    private function money(float $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
