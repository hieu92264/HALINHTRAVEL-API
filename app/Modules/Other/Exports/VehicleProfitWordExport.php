<?php

namespace App\Modules\Other\Exports;

use Carbon\Carbon;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class VehicleProfitWordExport
{
    /** @param array<int, array<string, mixed>> $rows */
    public function create(array $rows, string $fromDate, string $toDate): string
    {
        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(9);
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
        $this->addProfitTable($section, $rows);
        $this->addFooter($section);

        $temporary = tempnam(storage_path('app'), 'vehicle-profit-');
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
        $section->addText("BẢNG LỢI NHUẬN XE THÁNG {$month}", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        $section->addText(
            sprintf('(Từ ngày %s đến ngày %s)', Carbon::parse($fromDate)->format('d/m/Y'), Carbon::parse($toDate)->format('d/m/Y')),
            ['italic' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 180],
        );
    }

    /** @param array<int, array<string, mixed>> $rows
     * @param  Section  $section
     */
    private function addProfitTable($section, array $rows): void
    {
        $widths = [500, 1000, 800, 1250, 1350, 1300, 1300, 1250, 1250, 1250, 1000, 1250, 1250, 1250];
        $headers = [
            'STT', 'Biển số', 'Loại xe', 'Thu cước', 'Chi lương lái xe', 'Chi thuê xe', 'Chi thuê lái',
            'Chi phí dầu', 'Chi sửa chữa', 'Chi BH, ĐK', 'Chi khác', 'Chi phí chung PB', 'Tổng chi', 'Lợi nhuận',
        ];
        $fields = [
            'vehicle_plate', 'vehicle_type', 'revenue', 'driver_wage', 'partner_vehicle_cost', 'external_driver_cost',
            'fuel_cost', 'repair_cost', 'insurance_registration_cost', 'other_cost', 'general_cost_allocated', 'total_cost', 'profit',
        ];
        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => '000000',
            'cellMarginTop' => 55,
            'cellMarginBottom' => 55,
            'cellMarginLeft' => 35,
            'cellMarginRight' => 35,
            'alignment' => JcTable::CENTER,
        ]);
        $table->addRow(620, ['tblHeader' => true]);
        foreach ($headers as $index => $header) {
            $table->addCell($widths[$index], ['valign' => VerticalJc::CENTER])
                ->addText($header, ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER]);
        }

        $totals = array_fill_keys($fields, 0.0);
        foreach (array_values($rows) as $index => $row) {
            $table->addRow();
            $table->addCell($widths[0], ['valign' => VerticalJc::CENTER])
                ->addText((string) ($index + 1), [], ['alignment' => Jc::CENTER]);

            foreach ($fields as $column => $field) {
                $value = $row[$field] ?? '';
                $isText = in_array($field, ['vehicle_plate', 'vehicle_type'], true);
                if (! $isText) {
                    $value = (float) $value;
                    $totals[$field] += $value;
                    $value = $this->money($value);
                }
                $style = ['valign' => VerticalJc::CENTER];
                $font = $field === 'profit' && (float) ($row[$field] ?? 0) < 0 ? ['color' => 'C00000'] : [];
                $table->addCell($widths[$column + 1], $style)
                    ->addText((string) $value, $font, ['alignment' => $isText ? Jc::LEFT : Jc::RIGHT]);
            }
        }

        $table->addRow();
        $table->addCell($widths[0], ['gridSpan' => 3, 'valign' => VerticalJc::CENTER])
            ->addText('Tổng cộng', ['bold' => true], ['alignment' => Jc::CENTER]);
        foreach (array_slice($fields, 2) as $field) {
            $font = $field === 'profit' && $totals[$field] < 0 ? ['bold' => true, 'color' => 'C00000'] : ['bold' => true];
            $table->addCell($widths[3], ['valign' => VerticalJc::CENTER])
                ->addText($this->money($totals[$field]), $font, ['alignment' => Jc::RIGHT]);
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

    private function money(float $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
