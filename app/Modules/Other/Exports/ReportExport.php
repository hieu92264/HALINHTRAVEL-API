<?php

namespace App\Modules\Other\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithEvents, WithHeadings, WithStyles
{
    /** @param array<int, string> $headings
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    public function __construct(
        private readonly string $title,
        private readonly string $period,
        private readonly array $headings,
        private readonly array $rows,
        private readonly array $totals = [],
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $lastColumn = $sheet->getHighestColumn();
            $lastRow = $sheet->getHighestRow();
            $sheet->insertNewRowBefore(1, 4);
            $sheet->mergeCells("A1:{$lastColumn}1");
            $sheet->mergeCells("A2:{$lastColumn}2");
            $sheet->setCellValue('A1', config('app.name'));
            $sheet->setCellValue('A2', $this->title);
            $sheet->setCellValue('A3', $this->period);
            $sheet->getStyle("A1:{$lastColumn}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(15);
            $sheet->getStyle("A5:{$lastColumn}5")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A5:{$lastColumn}5")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
            $sheet->getStyle("A5:{$lastColumn}".($lastRow + 4))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->freezePane('A6');
            $sheet->setAutoFilter("A5:{$lastColumn}".($lastRow + 4));
            foreach (range('A', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            $sheet->getStyle("A6:{$lastColumn}".($lastRow + 4))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            if ($this->totals !== []) {
                $totalRow = $lastRow + 6;
                $sheet->mergeCells("A{$totalRow}:".($lastColumn === 'A' ? 'A' : chr(ord($lastColumn) - 1)).$totalRow);
                $sheet->setCellValue("A{$totalRow}", 'Tổng cộng');
                $sheet->setCellValue("{$lastColumn}{$totalRow}", implode(' | ', array_map(fn ($key, $value) => "{$key}: {$value}", array_keys($this->totals), $this->totals)));
                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->getFont()->setBold(true);
            }
            $signatureRow = $lastRow + 9;
            $sheet->setCellValue("A{$signatureRow}", 'GIÁM ĐỐC');
            $sheet->setCellValue('C'.$signatureRow, 'KẾ TOÁN');
            $sheet->setCellValue("{$lastColumn}{$signatureRow}", 'NGƯỜI LẬP');
        }];
    }
}
