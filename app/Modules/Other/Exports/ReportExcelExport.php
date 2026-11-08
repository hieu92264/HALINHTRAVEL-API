<?php

namespace App\Modules\Other\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ReportExcelExport implements FromArray, WithEvents, WithHeadings
{
    /**
     * @param array<string, string> $columns
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $totals
     */
    public function __construct(
        private readonly string $title,
        private readonly string $period,
        private readonly ?string $subjectName,
        private readonly array $columns,
        private readonly array $rows,
        private readonly array $totals,
    ) {}

    public function array(): array
    {
        return array_map(
            fn (array $row): array => array_map(fn (string $key) => $row[$key] ?? '', array_keys($this->columns)),
            $this->rows,
        );
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

            $sheet->insertNewRowBefore(1, 4);
            $headingRow = 5;
            $dataStartRow = 6;
            $dataEndRow = $dataStartRow + max(count($this->rows) - 1, 0);

            foreach ([1, 2, 3] as $row) {
                $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            }
            $sheet->setCellValue('A1', config('reports.company_name', config('app.name')));
            $sheet->setCellValue('A2', $this->title);
            $sheet->setCellValue('A3', $this->period);
            if ($this->subjectName !== null && $this->subjectName !== '') {
                $sheet->setCellValue('A4', 'Đối tượng: '.$this->subjectName);
                $sheet->mergeCells("A4:{$lastColumn}4");
            }

            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(10);
            $sheet->getStyle("A1:{$lastColumn}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A3')->getFont()->setItalic(true);
            $sheet->getRowDimension(1)->setRowHeight(20);
            $sheet->getRowDimension(2)->setRowHeight(24);

            $sheet->getStyle("A{$headingRow}:{$lastColumn}{$headingRow}")
                ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A{$headingRow}:{$lastColumn}{$headingRow}")
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
            $sheet->getStyle("A{$headingRow}:{$lastColumn}{$headingRow}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getRowDimension($headingRow)->setRowHeight(30);

            $sheet->getStyle("A{$headingRow}:{$lastColumn}{$dataEndRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('999999');
            $sheet->getStyle("A{$dataStartRow}:{$lastColumn}{$dataEndRow}")->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->freezePane('A6');
            if ($this->rows !== []) {
                $sheet->setAutoFilter("A{$headingRow}:{$lastColumn}{$dataEndRow}");
            }

            $totalRow = $dataEndRow + 1;
            if ($this->totals !== []) {
                $sheet->setCellValue("A{$totalRow}", 'Tổng cộng');
                foreach (array_keys($this->columns) as $index => $key) {
                    if ($index > 0 && array_key_exists($key, $this->totals)) {
                        $sheet->setCellValueByColumnAndRow($index + 1, $totalRow, $this->totals[$key]);
                    }
                }
                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAF1F8');
                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('999999');
            } else {
                $totalRow--;
            }

            $signatureDateRow = $totalRow + 3;
            $signatureRow = $signatureDateRow + 1;
            $leftEnd = max(1, (int) floor($columnCount / 3));
            $middleEnd = max($leftEnd + 1, (int) floor($columnCount * 2 / 3));
            $leftEndColumn = Coordinate::stringFromColumnIndex($leftEnd);
            $middleStartColumn = Coordinate::stringFromColumnIndex($leftEnd + 1);
            $middleEndColumn = Coordinate::stringFromColumnIndex($middleEnd);
            $rightStartColumn = Coordinate::stringFromColumnIndex($middleEnd + 1);

            $sheet->mergeCells("A{$signatureDateRow}:{$leftEndColumn}{$signatureDateRow}");
            $sheet->setCellValue("A{$signatureDateRow}", config('reports.city', 'Hải Phòng').', ngày '.now()->format('d').' tháng '.now()->format('m').' năm '.now()->format('Y'));
            $sheet->getStyle("A{$signatureDateRow}")->getFont()->setItalic(true);
            $sheet->getStyle("A{$signatureDateRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->mergeCells("A{$signatureRow}:{$leftEndColumn}{$signatureRow}");
            $sheet->mergeCells("{$middleStartColumn}{$signatureRow}:{$middleEndColumn}{$signatureRow}");
            $sheet->mergeCells("{$rightStartColumn}{$signatureRow}:{$lastColumn}{$signatureRow}");
            $sheet->setCellValue("A{$signatureRow}", 'GIÁM ĐỐC');
            $sheet->setCellValue("{$middleStartColumn}{$signatureRow}", 'KẾ TOÁN');
            $sheet->setCellValue("{$rightStartColumn}{$signatureRow}", 'NGƯỜI LẬP');
            $sheet->getStyle("A{$signatureRow}:{$lastColumn}{$signatureRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$signatureRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("{$middleStartColumn}{$signatureRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$rightStartColumn}{$signatureRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            foreach (range(1, $columnCount) as $index) {
                $column = Coordinate::stringFromColumnIndex($index);
                $label = array_values($this->columns)[$index - 1];
                $sheet->getColumnDimension($column)->setWidth(mb_strlen($label) > 16 ? 22 : 14);
            }

            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
            $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setFitToPage(true);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);
            $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$signatureRow}");
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headingRow, $headingRow);
        }];
    }
}
