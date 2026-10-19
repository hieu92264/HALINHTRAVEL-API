<?php

namespace App\Modules\Other\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export for Chi tiết công nợ khách hàng / Chi tiết công nợ chủ xe.
 *
 * Layout mirrors the printed UI report:
 *  Row 1 – Company name (left) | state header (right)
 *  Row 2 – (empty)             | "Độc lập – Tự do – Hạnh phúc"
 *  Row 3 – (empty)
 *  Row 4 – Report title (centered, full-width)
 *  Row 5 – Period (centered, full-width)
 *  Row 6 – Subject label (left)  | Opening-balance label (right)
 *  Row 7 – Table headings
 *  Row 8..N – Data rows
 *  Row N+1 – Totals row
 *  Row N+2 – Closing-balance note (right)
 *  Row N+4 – City/date (right)
 *  Row N+5 – Signature row: NGƯỜI LẬP | KẾ TOÁN | GIÁM ĐỐC
 *
 * Print: A4 landscape, fit-to-width, 10pt body font, narrow margins.
 */
class DebtDetailExport implements FromArray, WithEvents
{
    private const STATE_TITLE = 'CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM';

    private const STATE_SUBTITLE = 'Độc lập - Tự do - Hạnh phúc';

    // Column widths (characters) for the 10 standard columns.
    // Adjust individually rather than auto-size so the sheet fits A4 landscape.
    private const COL_WIDTHS = [
        'A' => 5,   // Stt
        'B' => 10,  // Ngày
        'C' => 36,  // Diễn giải/Hành trình  (wider – wraps, so more height not width)
        'D' => 10,  // Biển số
        'E' => 8,   // Giờ đón
        'F' => 8,   // Giờ về
        'G' => 22,  // Ghi chú
        'H' => 13,  // Nợ phát sinh
        'I' => 13,  // Có phát sinh
        'J' => 13,  // Nợ cuối kỳ
    ];

    // Columns that contain long text and must wrap
    private const WRAP_COLS = ['C', 'G'];

    /**
     * @param  array<int,string>  $headings  Column headings for the data table
     * @param  array<int,array<int,scalar|null>>  $rows  Data rows
     * @param  string  $title  Report title
     * @param  string  $period  Period string
     * @param  string  $subjectLabel  Entity label, e.g. "Khách hàng: AMI"
     * @param  string  $openingBalanceLabel  Opening-balance label
     * @param  string  $closingBalanceLabel  Closing-balance label
     * @param  array<int,scalar|null>  $totals  Totals row values
     * @param  string|null  $cityDateLine  City/date line printed bottom-right
     */
    public function __construct(
        private readonly string $title,
        private readonly string $period,
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $subjectLabel = '',
        private readonly string $openingBalanceLabel = '',
        private readonly string $closingBalanceLabel = '',
        private readonly array $totals = [],
        private readonly ?string $cityDateLine = null,
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
            $colCount = Coordinate::columnIndexFromString($lastCol);

            // ── Insert 7 header rows ──────────────────────────────────────────
            $sheet->insertNewRowBefore(1, 7);
            $lastDataRow = $dataLastRow + 7;

            $midCol = $this->columnLetter((int) ceil($colCount / 2) + 1);
            $afterMid = $this->nextColumn($midCol);

            // Row 1 – Company | State title
            $sheet->setCellValue('A1', config('app.name', 'CÔNG TY TNHH VẬN TẢI & DU LỊCH HÀ LINH'));
            $sheet->mergeCells("A1:{$midCol}1");
            $sheet->setCellValue("{$afterMid}1", self::STATE_TITLE);
            $sheet->mergeCells("{$afterMid}1:{$lastCol}1");

            // Row 2 – State subtitle (right)
            $sheet->setCellValue("{$afterMid}2", self::STATE_SUBTITLE);
            $sheet->mergeCells("{$afterMid}2:{$lastCol}2");

            // Row 4 – Title
            $sheet->mergeCells("A4:{$lastCol}4");
            $sheet->setCellValue('A4', $this->title);

            // Row 5 – Period
            $sheet->mergeCells("A5:{$lastCol}5");
            $sheet->setCellValue('A5', $this->period);

            // Row 6 – Subject | Opening balance
            $sheet->setCellValue('A6', $this->subjectLabel);
            $sheet->mergeCells("A6:{$midCol}6");
            if ($this->openingBalanceLabel !== '') {
                $sheet->setCellValue("{$afterMid}6", $this->openingBalanceLabel);
                $sheet->mergeCells("{$afterMid}6:{$lastCol}6");
            }

            // Row 7 – Table headings
            foreach ($this->headings as $i => $heading) {
                $sheet->setCellValueByColumnAndRow($i + 1, 7, $heading);
            }

            // ── Totals row ────────────────────────────────────────────────────
            $totalsRow = $lastDataRow + 1;
            if ($this->totals !== []) {
                foreach ($this->totals as $i => $value) {
                    $sheet->setCellValueByColumnAndRow($i + 1, $totalsRow, $value);
                }
                $sheet->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->getFont()->setBold(true);
            }

            // ── Closing-balance note ──────────────────────────────────────────
            if ($this->closingBalanceLabel !== '') {
                $noteRow = $totalsRow + 1;
                $sheet->setCellValue("{$afterMid}{$noteRow}", $this->closingBalanceLabel);
                $sheet->mergeCells("{$afterMid}{$noteRow}:{$lastCol}{$noteRow}");
                $sheet->getStyle("{$afterMid}{$noteRow}")->getFont()->setBold(true);
                $sheet->getStyle("{$afterMid}{$noteRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // ── City / date ───────────────────────────────────────────────────
            $cityRow = $totalsRow + 3;
            if ($this->cityDateLine !== null) {
                $sheet->setCellValue("{$afterMid}{$cityRow}", $this->cityDateLine);
                $sheet->mergeCells("{$afterMid}{$cityRow}:{$lastCol}{$cityRow}");
                $sheet->getStyle("{$afterMid}{$cityRow}")->getFont()->setItalic(true);
                $sheet->getStyle("{$afterMid}{$cityRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // ── Signature row ─────────────────────────────────────────────────
            $sigRow = $cityRow + 1;
            $sheet->setCellValue("A{$sigRow}", 'NGƯỜI LẬP');
            $sheet->setCellValueByColumnAndRow((int) ceil($colCount / 2), $sigRow, 'KẾ TOÁN');
            $sheet->setCellValue("{$lastCol}{$sigRow}", 'GIÁM ĐỐC');

            // ══════════════════════════════════════════════════════════════════
            // F O N T  &  S T Y L I N G
            // ══════════════════════════════════════════════════════════════════
            $bodyFont = 10;

            // Default font for whole sheet
            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize($bodyFont);

            // Company name
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A1')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getRowDimension(1)->setRowHeight(28);

            // State header
            $sheet->getStyle("{$afterMid}1:{$lastCol}2")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("{$afterMid}1")->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle("{$afterMid}2")->getFont()->setItalic(true)->setUnderline(true)->setSize(10);

            // Title
            $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A4')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(4)->setRowHeight(22);

            // Period
            $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension(5)->setRowHeight(16);

            // Subject / opening balance row
            $sheet->getStyle("A6:{$lastCol}6")
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A6')->getFont()->setBold(true);
            $sheet->getStyle("{$afterMid}6")->getFont()->setBold(true);
            $sheet->getStyle("{$afterMid}6")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension(6)->setRowHeight(16);

            // Headings row – dark navy background, white bold text
            $sheet->getStyle("A7:{$lastCol}7")
                ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A7:{$lastCol}7")
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
            $sheet->getStyle("A7:{$lastCol}7")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getRowDimension(7)->setRowHeight(28);
            $this->applyThinBorders($sheet, "A7:{$lastCol}7");

            // Data rows
            $dataStart = 8;
            $dataEnd = $lastDataRow;
            if ($dataStart <= $dataEnd) {
                $sheet->getStyle("A{$dataStart}:{$lastCol}{$dataEnd}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP);  // top-align so text reads from top when it wraps
                // Text columns – wrap so content is never clipped
                foreach (self::WRAP_COLS as $wrapCol) {
                    $sheet->getStyle("{$wrapCol}{$dataStart}:{$wrapCol}{$dataEnd}")
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
                // Numeric columns (H, I, J) right-aligned, no wrap
                $sheet->getStyle("H{$dataStart}:{$lastCol}{$dataEnd}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setWrapText(false);
                $sheet->getStyle("H{$dataStart}:{$lastCol}{$dataEnd}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
                $this->applyThinBorders($sheet, "A{$dataStart}:{$lastCol}{$dataEnd}");
                // Auto row-height: -1 tells Excel to calculate height from wrapped content
                for ($r = $dataStart; $r <= $dataEnd; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(-1);
                }
            }

            // Totals row borders & format
            if ($this->totals !== []) {
                $this->applyThinBorders($sheet, "A{$totalsRow}:{$lastCol}{$totalsRow}");
                $sheet->getStyle("H{$totalsRow}:{$lastCol}{$totalsRow}")
                    ->getNumberFormat()->setFormatCode('#,##0');
                // Wrap the "Tổng cộng" label cell too
                $sheet->getStyle("B{$totalsRow}")
                    ->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($totalsRow)->setRowHeight(-1);
            }

            // ── Column widths (fixed for A4 landscape fit) ────────────────────
            foreach (self::COL_WIDTHS as $col => $width) {
                if (Coordinate::columnIndexFromString($col) <= $colCount) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            }
            // Any extra columns beyond J get auto-sized
            if ($colCount > count(self::COL_WIDTHS)) {
                for ($i = count(self::COL_WIDTHS) + 1; $i <= $colCount; $i++) {
                    $sheet->getColumnDimension($this->columnLetter($i))->setAutoSize(true);
                }
            }

            // Signature
            $sheet->getStyle("A{$sigRow}:{$lastCol}{$sigRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$sigRow}:{$lastCol}{$sigRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($sigRow)->setRowHeight(30);

            // Freeze pane
            $sheet->freezePane('A8');

            // ══════════════════════════════════════════════════════════════════
            // P R I N T  /  P A G E  S E T U P
            // ══════════════════════════════════════════════════════════════════
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
            $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setFitToPage(true);
            $pageSetup->setFitToWidth(1);   // force everything into 1 page wide
            $pageSetup->setFitToHeight(0);  // unlimited pages tall

            // Print area covers header + data + footer (signatures)
            $sheet->getPageSetup()->setPrintArea("A1:{$lastCol}{$sigRow}");

            // Repeat headings row on every printed page
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(7, 7);

            // Narrow margins (in inches) matching the UI report
            $margins = $sheet->getPageMargins();
            $margins->setTop(0.5);
            $margins->setBottom(0.5);
            $margins->setLeft(0.5);
            $margins->setRight(0.4);
            $margins->setHeader(0.2);
            $margins->setFooter(0.2);

            // Page number in footer (centre)
            $sheet->getHeaderFooter()->setOddFooter('&C&P');
        }];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function columnLetter(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }

    private function nextColumn(string $col): string
    {
        return $this->columnLetter(Coordinate::columnIndexFromString($col) + 1);
    }

    private function applyThinBorders(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
