<?php

namespace App\Modules\Other\Exports;

use Carbon\Carbon;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class TourismTripWordExport
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

        $temporary = tempnam(storage_path('app'), 'tourism-trips-');
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
        $section->addText('BÁO CÁO XE CHẠY DU LỊCH', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
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
        $widths = [600, 1080, 1200, 1680, 1680, 1200, 600, 960, 1050, 1050, 1080, 1080, 1200, 1200];
        $headers = [
            'STT', 'Ngày', 'Biển số', 'Hành trình', 'Tên lái xe', 'Cước xe', '%', 'Lương lái xe',
            'Tiền thuê lái', 'Tiền thuê xe', 'Lái xe thu', 'Lái xe nộp', 'Công ty thu', 'Còn lại',
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
        $table->addRow(600, ['tblHeader' => true]);
        foreach ($headers as $index => $header) {
            $table->addCell($widths[$index], ['valign' => VerticalJc::CENTER])
                ->addText($header, ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER]);
        }

        $totals = [
            'revenue' => 0.0,
            'driver_wage' => 0.0,
            'external_driver_cost' => 0.0,
            'partner_vehicle_cost' => 0.0,
            'driver_collected_amount' => 0.0,
            'driver_remitted_amount' => 0.0,
            'company_collected_amount' => 0.0,
            'remaining_receivable' => 0.0,
        ];
        foreach (array_values($rows) as $index => $row) {
            foreach (array_keys($totals) as $field) {
                $totals[$field] += (float) ($row[$field] ?? 0);
            }
            $revenue = (float) ($row['revenue'] ?? 0);
            $driverWage = (float) ($row['driver_wage'] ?? 0);
            $remaining = (float) ($row['remaining_receivable'] ?? 0);
            $values = [
                $index + 1,
                $this->date($row['date'] ?? null),
                $row['vehicle_plate'] ?? '',
                ($row['journey'] ?? '') ?: ($row['route_name'] ?? ''),
                $row['driver_name'] ?? '',
                $this->money($revenue),
                $this->rate($driverWage, $revenue),
                $this->money($driverWage),
                $this->money((float) ($row['external_driver_cost'] ?? 0)),
                $this->money((float) ($row['partner_vehicle_cost'] ?? 0)),
                $this->money((float) ($row['driver_collected_amount'] ?? 0)),
                $this->money((float) ($row['driver_remitted_amount'] ?? 0)),
                $this->money((float) ($row['company_collected_amount'] ?? 0)),
                $this->money($remaining),
            ];
            $table->addRow();
            foreach ($values as $column => $value) {
                $alignment = in_array($column, [0, 1, 2, 6], true)
                    ? Jc::CENTER
                    : (in_array($column, [5, 7, 8, 9, 10, 11, 12, 13], true) ? Jc::RIGHT : Jc::LEFT);
                $font = $column === 13 && $remaining < 0 ? ['color' => 'C00000'] : [];
                $table->addCell($widths[$column], ['valign' => VerticalJc::CENTER])
                    ->addText((string) $value, $font, ['alignment' => $alignment]);
            }
        }

        $table->addRow();
        $table->addCell($widths[0], ['gridSpan' => 5, 'valign' => VerticalJc::CENTER])
            ->addText('Tổng cộng', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell($widths[5], ['valign' => VerticalJc::CENTER])
            ->addText($this->money($totals['revenue']), ['bold' => true], ['alignment' => Jc::RIGHT]);
        $table->addCell($widths[6]);
        foreach (['driver_wage', 'external_driver_cost', 'partner_vehicle_cost', 'driver_collected_amount', 'driver_remitted_amount', 'company_collected_amount', 'remaining_receivable'] as $field) {
            $font = $field === 'remaining_receivable' && $totals[$field] < 0 ? ['bold' => true, 'color' => 'C00000'] : ['bold' => true];
            $table->addCell($widths[7], ['valign' => VerticalJc::CENTER])
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

    private function rate(float $driverWage, float $revenue): string
    {
        if ($revenue <= 0) {
            return '0';
        }

        return rtrim(rtrim(number_format($driverWage / $revenue * 100, 2, '.', ''), '0'), '.');
    }

    private function money(float $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
