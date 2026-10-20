<?php

namespace App\Modules\Other\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export for "Bảng lợi nhuận xe tháng X/YYYY".
 *
 * Layout:
 *  Row 1 – Company name (left, bold)
 *  Row 2 – (empty separator)
 *  Row 3 – Report title (centred, large bold)
 *  Row 4 – Table heading row (single level, wrap)
 *  Row 5..N – Data rows
 *  Row N+1 – Totals row (bold)
 *  Row N+3 – Signature: NGƯỜI LẬP
 *
 * 13 columns: Stt | Biển số | Loại xe | Thu cước | Chi lương lái xe |
 *             Chi thuê lái | Chi phí dầu | Chi sửa chữa | Chi BH,ĐK |
 *             Chi khác | Chi phí chung PB | Tổng chi | Lợi nhuận
 *
 * Print: A4 landscape, fit-to-width, Times New Roman 10pt, narrow margins.
 */
class VehicleProfitExport implements FromArray, WithEvents
{
    // Fixed column widths (characters) – tuned for A4 landscape with 13 cols
    private const COL_WIDTHS = [
        'A' => 5,   // Stt
        'B' => 12,  // Biển số
        'C' => 9,   // Loại xe
        'D' => 12,  // Thu cước
        'E' => 12,  // Chi lương lái xe
        'F' => 10,  // Chi thuê lái
        'G' => 10,  // Chi phí dầu
        'H' => 10,  // Chi sửa chữa
        'I' => 10,  // Chi BH, ĐK
        'J' => 10,  // Chi khác
        'K' => 11,  // Chi phí chung PB
        'L' => 12,  // Tổng chi
        'M' => 12,  // Lợi nhuận
    ];

    // Columns with long text that must wrap
    private const WRAP_COLS = ['B', 'C'];

    /**
     * @param  string  $title  Report title
     * @param  array<int,string>  $headings  Column headings (13 items)
     * @param  array<int,array<int,scalar|null>>  $rows  Data rows
     * @param  array<int,scalar|null>  $totals  Totals row values
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly array $rows,
        private readonly array $totals = [],
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $lastCol = $sheet->getHighestColumn();
            $dataLastRow = $sheet->getHighestRow();

            // ── Insert 4 header rows ──────────────────────────────────────────
            $sheet->insertNewRowBefore(1, 4);
            $lastDataRow = $dataLastRow + 4;

            // ── Row 1: Company name ───────────────────────────────────────────
            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->setCellValue('A1', config('app.name', 'CÔNG TY TNHH VẬN TẢI & DU LỊCH HÀ LINH'));

            // ── Row 3: Title ──────────────────────────────────────────────────
            $sheet->mergeCells("A3:{$lastCol}3");
            $sheet->setCellValue('A3', $this->title);

            // ── Row 4: Table headings ─────────────────────────────────────────
            foreach ($this->headings as $i => $heading) {
                $sheet->setCellValueByColumnAndRow($i + 1, 4, $heading);
            }

            // ── Totals row ────────────────────────────────────────────────────
            $totalsRow = $lastDataRow + 1;
            if ($this->totals !== []) {
                foreach ($this->totals as $i => $value) {
                    $sheet->setCellValueByColumnAndRow($i + 1, $totalsRow, $value);
                }
                $sheet->mergeCells("A{$totalsRow}:C{$totalsRow}");
                $sheet->getStyle("A{$totalsRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->getFont()->setBold(true);
            }

            // ── Signature ─────────────────────────────────────────────────────
            $sigRow = $totalsRow + 2;
            $sheet->setCellValue("A{$sigRow}", 'NGƯỜI LẬP');

            // ══════════════════════════════════════════════════════════════════
            // F O N T  &  S T Y L I N G
            // ══════════════════════════════════════════════════════════════════
            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(10);

            // Company name
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A1')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(22);

            // Title
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension(3)->setRowHeight(22);

            // Heading row 4
            $sheet->getStyle("A4:{$lastCol}4")->getFont()->setBold(true);
            $sheet->getStyle("A4:{$lastCol}4")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getRowDimension(4)->setRowHeight(36);
            $this->applyThinBorders($sheet, "A4:{$lastCol}4");

            // Data rows
            $dataStart = 5;
            $dataEnd = $lastDataRow;
            if ($dataStart <= $dataEnd) {
                $sheet->getStyle("A{$dataStart}:{$lastCol}{$dataEnd}")
                    ->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                // Text columns (B=Biển số, C=Loại xe): wrap so content never clips
                foreach (self::WRAP_COLS as $wrapCol) {
                    $sheet->getStyle("{$wrapCol}{$dataStart}:{$wrapCol}{$dataEnd}")
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                // Numeric columns right-aligned, no wrap (D onwards)
                $sheet->getStyle("D{$dataStart}:{$lastCol}{$dataEnd}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setWrapText(false);
                $sheet->getStyle("D{$dataStart}:{$lastCol}{$dataEnd}")
                    ->getNumberFormat()->setFormatCode('#,##0');
                $this->applyThinBorders($sheet, "A{$dataStart}:{$lastCol}{$dataEnd}");
                // Auto row-height: -1 lets Excel expand rows to fit wrapped text
                for ($r = $dataStart; $r <= $dataEnd; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(-1);
                }
            }

            // Totals row borders & format
            $this->applyThinBorders($sheet, "A{$totalsRow}:{$lastCol}{$totalsRow}");
            $sheet->getStyle("D{$totalsRow}:{$lastCol}{$totalsRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$totalsRow}:{$lastCol}{$totalsRow}")
                ->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getRowDimension($totalsRow)->setRowHeight(-1);

            // ── Column widths (fixed for A4 landscape) ────────────────────────
            foreach (self::COL_WIDTHS as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            // Signature
            $sheet->getStyle("A{$sigRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$sigRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($sigRow)->setRowHeight(28);

            $sheet->freezePane('A5');

            // ══════════════════════════════════════════════════════════════════
            // P R I N T  /  P A G E  S E T U P
            // ══════════════════════════════════════════════════════════════════
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
            $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setFitToPage(true);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);

            $sheet->getPageSetup()->setPrintArea("A1:{$lastCol}{$sigRow}");
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(4, 4);

            $margins = $sheet->getPageMargins();
            $margins->setTop(0.5);
            $margins->setBottom(0.5);
            $margins->setLeft(0.4);
            $margins->setRight(0.4);
            $margins->setHeader(0.2);
            $margins->setFooter(0.2);

            $sheet->getHeaderFooter()->setOddFooter('&C&P');
        }];
    }

    private function applyThinBorders(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
