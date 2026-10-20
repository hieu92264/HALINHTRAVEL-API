<?php

namespace App\Modules\Other\Exports;

use Carbon\Carbon;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class FixedTripWordExport
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
        $this->addTripTable($section, $rows);
        $this->addFooter($section);

        $temporary = tempnam(storage_path('app'), 'fixed-trips-');
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
        $section->addText('BÁO CÁO XE CHẠY CÔNG NHÂN', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        $section->addText(
            sprintf('(Từ ngày %s đến ngày %s)', Carbon::parse($fromDate)->format('d/m/Y'), Carbon::parse($toDate)->format('d/m/Y')),
            ['italic' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 180],
        );
    }

    /** @param array<int, array<string, mixed>> $rows
     * @param  Section  $section
     */
    private function addTripTable($section, array $rows): void
    {
        $widths = [600, 1080, 1200, 1320, 1560, 600, 600, 1680, 1320, 1200, 960, 1095, 1305, 1110];
        $headers = [
            'STT', 'Ngày', 'Biển số', 'Khách hàng', 'Tên tuyến', 'Giờ đón', 'Giờ về', 'Tên lái xe',
            'Cước xe', 'Lương lái xe', 'Tiền thuê lái', 'Tiền thuê xe', 'Lợi nhuận', 'Chủ xe',
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
        $table->addRow(500, ['tblHeader' => true]);
        foreach ($headers as $index => $header) {
            $table->addCell($widths[$index], ['valign' => VerticalJc::CENTER])
                ->addText($header, ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER]);
        }

        $totals = [
            'revenue' => 0.0,
            'driver_wage' => 0.0,
            'external_driver_cost' => 0.0,
            'partner_vehicle_cost' => 0.0,
            'external_vehicle_profit' => 0.0,
        ];
        foreach (array_values($rows) as $index => $row) {
            foreach (array_keys($totals) as $field) {
                $totals[$field] += (float) ($row[$field] ?? 0);
            }
            $profit = (float) ($row['external_vehicle_profit'] ?? 0);
            $values = [
                $index + 1,
                $this->date($row['date'] ?? null),
                $row['vehicle_plate'] ?? '',
                $row['customer_name'] ?? '',
                ($row['journey'] ?? '') ?: ($row['route_name'] ?? ''),
                $row['pickup_time'] ?? '',
                $row['return_time'] ?? '',
                $row['driver_name'] ?? '',
                $this->money((float) ($row['revenue'] ?? 0)),
                $this->money((float) ($row['driver_wage'] ?? 0)),
                $this->money((float) ($row['external_driver_cost'] ?? 0)),
                $this->money((float) ($row['partner_vehicle_cost'] ?? 0)),
                $this->money($profit),
                $row['partner_name'] ?? '',
            ];
            $table->addRow();
            foreach ($values as $column => $value) {
                $alignment = in_array($column, [0, 1, 2, 5, 6], true)
                    ? Jc::CENTER
                    : (in_array($column, [8, 9, 10, 11, 12], true) ? Jc::RIGHT : Jc::LEFT);
                $font = $column === 12 && $profit < 0 ? ['color' => 'C00000'] : [];
                $table->addCell($widths[$column], ['valign' => VerticalJc::CENTER])
                    ->addText((string) $value, $font, ['alignment' => $alignment]);
            }
        }

        $table->addRow();
        $table->addCell($widths[0], ['gridSpan' => 8, 'valign' => VerticalJc::CENTER])
            ->addText('Tổng cộng', ['bold' => true], ['alignment' => Jc::CENTER]);
        foreach (['revenue', 'driver_wage', 'external_driver_cost', 'partner_vehicle_cost', 'external_vehicle_profit'] as $field) {
            $font = $field === 'external_vehicle_profit' && $totals[$field] < 0 ? ['bold' => true, 'color' => 'C00000'] : ['bold' => true];
            $table->addCell($widths[8], ['valign' => VerticalJc::CENTER])
                ->addText($this->money($totals[$field]), $font, ['alignment' => Jc::RIGHT]);
        }
        $table->addCell($widths[13]);
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
        $table->addCell(10600);
        $table->addCell(5000, ['valign' => VerticalJc::CENTER])
            ->addText('NGƯỜI LẬP', ['bold' => true], ['alignment' => Jc::CENTER]);
    }

    /** @param Section $section */
    private function addPageNumber($section): void
    {
        $section->addFooter()->addPreserveText('{PAGE}', [], ['alignment' => Jc::RIGHT]);
    }

    private function date(mixed $value): string
    {
        return blank($value) ? '' : Carbon::parse($value)->format('d/m/Y');
    }

    private function money(float $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
