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
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
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

            // ── Insert 4 header rows ──────────────────────────────────────────
            $sheet->insertNewRowBefore(1, 4);
            $sheet->mergeCells("A1:{$lastColumn}1");
            $sheet->mergeCells("A2:{$lastColumn}2");
            $sheet->mergeCells("A3:{$lastColumn}3");
            $sheet->setCellValue('A1', config('app.name'));
            $sheet->setCellValue('A2', $this->title);
            $sheet->setCellValue('A3', $this->period);

            // ── Default font ──────────────────────────────────────────────────
            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(10);

            // ── Header rows styling ───────────────────────────────────────────
            $sheet->getStyle("A1:{$lastColumn}3")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A3')->getFont()->setItalic(true);
            $sheet->getRowDimension(1)->setRowHeight(20);
            $sheet->getRowDimension(2)->setRowHeight(22);
            $sheet->getRowDimension(3)->setRowHeight(16);

            // ── Heading row (row 5 after insertion) ───────────────────────────
            $sheet->getStyle("A5:{$lastColumn}5")
                ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A5:{$lastColumn}5")
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
            $sheet->getStyle("A5:{$lastColumn}5")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getRowDimension(5)->setRowHeight(28);

            // ── Data rows ─────────────────────────────────────────────────────
            $dataEnd = $lastRow + 4;
            $sheet->getStyle("A6:{$lastColumn}{$dataEnd}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->freezePane('A6');
            $sheet->setAutoFilter("A5:{$lastColumn}{$dataEnd}");

            // Number format for all data
            $sheet->getStyle("A6:{$lastColumn}{$dataEnd}")
                ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

            // Row heights for data
            for ($r = 6; $r <= $dataEnd; $r++) {
                $sheet->getRowDimension($r)->setRowHeight(16);
            }

            // ── Totals row ────────────────────────────────────────────────────
            if ($this->totals !== []) {
                $totalRow = $lastRow + 6;
                $sheet->mergeCells('A'.$totalRow.':'.($lastColumn === 'A' ? 'A' : chr(ord($lastColumn) - 1)).$totalRow);
                $sheet->setCellValue("A{$totalRow}", 'Tổng cộng');
                $sheet->setCellValue("{$lastColumn}{$totalRow}", implode(' | ', array_map(fn ($key, $value) => "{$key}: {$value}", array_keys($this->totals), $this->totals)));
                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->getFont()->setBold(true);
                $sheet->getRowDimension($totalRow)->setRowHeight(16);
            }

            // ── Signature row ─────────────────────────────────────────────────
            $signatureRow = $lastRow + 9;
            $sheet->setCellValue("A{$signatureRow}", 'GIÁM ĐỐC');
            $sheet->setCellValue('C'.$signatureRow, 'KẾ TOÁN');
            $sheet->setCellValue("{$lastColumn}{$signatureRow}", 'NGƯỜI LẬP');
            $sheet->getStyle("A{$signatureRow}:{$lastColumn}{$signatureRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$signatureRow}:{$lastColumn}{$signatureRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($signatureRow)->setRowHeight(28);

            // ── Auto-size columns (generic report – column count unknown) ─────
            foreach (range('A', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // ══════════════════════════════════════════════════════════════════
            // P R I N T  /  P A G E  S E T U P
            // ══════════════════════════════════════════════════════════════════
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
            $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setFitToPage(true);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);

            $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$signatureRow}");
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(5, 5);

            $margins = $sheet->getPageMargins();
            $margins->setTop(0.5);
            $margins->setBottom(0.5);
            $margins->setLeft(0.5);
            $margins->setRight(0.4);
            $margins->setHeader(0.2);
            $margins->setFooter(0.2);

            $sheet->getHeaderFooter()->setOddFooter('&C&P');
        }];
    }
}
