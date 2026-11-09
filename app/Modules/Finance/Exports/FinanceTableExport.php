<?php

namespace App\Modules\Finance\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class FinanceTableExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithEvents, WithHeadings
{
    /** @param array<string, string> $columns @param array<int, array<string, string>> $rows */
    public function __construct(private readonly string $title, private readonly array $columns, private readonly array $rows) {}

    public function array(): array
    {
        return array_map(fn (array $row) => array_map(fn (string $key) => $row[$key] ?? '', array_keys($this->columns)), $this->rows);
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $lastColumn = $sheet->getHighestColumn();
            $columnCount = Coordinate::columnIndexFromString($lastColumn);
            $sheet->insertNewRowBefore(1, 2);
            $headingRow = 3;
            $dataStartRow = 4;
            $dataEndRow = $dataStartRow + max(count($this->rows) - 1, 0);
            $sheet->mergeCells("A1:{$lastColumn}1");
            $sheet->mergeCells("A2:{$lastColumn}2");
            $sheet->setCellValue('A1', config('reports.company_name', config('app.name')));
            $sheet->setCellValue('A2', $this->title);
            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(10);
            $sheet->getStyle("A1:{$lastColumn}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$headingRow}:{$lastColumn}{$headingRow}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A{$headingRow}:{$lastColumn}{$headingRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
            $sheet->getStyle("A{$headingRow}:{$lastColumn}{$dataEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('999999');
            $sheet->getStyle("A{$headingRow}:{$lastColumn}{$dataEndRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle("A{$headingRow}:{$lastColumn}{$headingRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($headingRow)->setRowHeight(30);
            $signatureRow = $dataEndRow + 4;
            $sheet->mergeCells("A{$signatureRow}:{$lastColumn}{$signatureRow}");
            $sheet->setCellValue("A{$signatureRow}", 'NGƯỜI LẬP');
            $sheet->getStyle("A{$signatureRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$signatureRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            foreach (range(1, $columnCount) as $index) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth(18);
            }
            $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$signatureRow}");
        }];
    }
}
