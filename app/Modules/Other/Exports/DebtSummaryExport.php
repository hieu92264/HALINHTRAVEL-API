<?php

namespace App\Modules\Other\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export for "Thống kê công nợ các chủ xe" / "Bảng tổng hợp công nợ khách hàng".
 *
 * Layout:
 *  Row 1 – Company name (left, bold)
 *  Row 2 – (empty separator)
 *  Row 3 – Report title (centred, large bold)
 *  Row 4 – Period (centred, italic)
 *  Row 5 – Heading row 1 (Stt | Subject | Nợ đầu | Phát sinh trong kỳ… | Dư cuối kỳ)
 *  Row 6 – Heading row 2 (sub-cols: Nợ | Có)
 *  Row 7..N – Data rows
 *  Row N+1 – Totals row
 *  Row N+3 – City/date (right, italic)
 *  Row N+4 – Signature row
 *
 * Print: A4 portrait, fit-to-width, Times New Roman 10pt, narrow margins.
 */
class DebtSummaryExport implements FromArray, WithEvents
{
    // Fixed column widths (characters) – 6 columns to fit A4 portrait
    private const COL_WIDTHS = [
        'A' => 6,   // Stt
        'B' => 30,  // Tên khách/chủ xe
        'C' => 16,  // Nợ đầu
        'D' => 16,  // Phát sinh Nợ
        'E' => 14,  // Phát sinh Có
        'F' => 16,  // Dư cuối kỳ
    ];

    // Columns with long text that must wrap
    private const WRAP_COLS = ['B'];

    /**
     * @param  string  $title  Report title
     * @param  string  $period  Period string
     * @param  string  $subjectCol  Header for the name column
     * @param  array<int,array<int,scalar|null>>  $rows  Data rows
     * @param  array<int,scalar|null>  $totals  Totals row
     * @param  string|null  $cityDateLine  City/date footer line
     */
    public function __construct(
        private readonly string $title,
        private readonly string $period,
        private readonly string $subjectCol,
        private readonly array $rows,
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

            // ── Insert 6 header rows ──────────────────────────────────────────
            $sheet->insertNewRowBefore(1, 6);
            $lastDataRow = $dataLastRow + 6;

            // ── Row 1: Company name ───────────────────────────────────────────
            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->setCellValue('A1', config('app.name', 'CÔNG TY TNHH VẬN TẢI & DU LỊCH HÀ LINH'));

            // ── Row 3: Title ──────────────────────────────────────────────────
            $sheet->mergeCells("A3:{$lastCol}3");
            $sheet->setCellValue('A3', $this->title);

            // ── Row 4: Period ─────────────────────────────────────────────────
            $sheet->mergeCells("A4:{$lastCol}4");
            $sheet->setCellValue('A4', $this->period);

            // ── Rows 5–6: Two-level column headings ───────────────────────────
            $sheet->setCellValue('A5', 'Stt');
            $sheet->mergeCells('A5:A6');
            $sheet->setCellValue('B5', $this->subjectCol);
            $sheet->mergeCells('B5:B6');
            $sheet->setCellValue('C5', 'Nợ đầu');
            $sheet->mergeCells('C5:C6');
            $sheet->setCellValue('D5', 'Phát sinh trong kỳ');
            $sheet->mergeCells('D5:E5');
            $sheet->setCellValue('F5', 'Dư cuối kỳ');
            $sheet->mergeCells('F5:F6');
            $sheet->setCellValue('D6', 'Nợ');
            $sheet->setCellValue('E6', 'Có');

            // ── Totals row ────────────────────────────────────────────────────
            $totalsRow = $lastDataRow + 1;
            if ($this->totals !== []) {
                foreach ($this->totals as $i => $value) {
                    $sheet->setCellValueByColumnAndRow($i + 1, $totalsRow, $value);
                }
                $sheet->mergeCells("A{$totalsRow}:C{$totalsRow}");
                $sheet->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalsRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // ── City / date ───────────────────────────────────────────────────
            $cityRow = $totalsRow + 2;
            if ($this->cityDateLine !== null) {
                $sheet->mergeCells("D{$cityRow}:{$lastCol}{$cityRow}");
                $sheet->setCellValue("D{$cityRow}", $this->cityDateLine);
                $sheet->getStyle("D{$cityRow}")->getFont()->setItalic(true);
                $sheet->getStyle("D{$cityRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // ── Signatures ────────────────────────────────────────────────────
            $sigRow = $cityRow + 1;
            $sheet->setCellValue("A{$sigRow}", 'NGƯỜI LẬP');
            $midIdx = (int) ceil($colCount / 2);
            $sheet->setCellValueByColumnAndRow($midIdx, $sigRow, 'KẾ TOÁN');
            $sheet->setCellValue("{$lastCol}{$sigRow}", 'GIÁM ĐỐC');

            // ══════════════════════════════════════════════════════════════════
            // F O N T  &  S T Y L I N G
            // ══════════════════════════════════════════════════════════════════
            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(10);

            // Company name
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A1')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getRowDimension(1)->setRowHeight(26);

            // Title
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(15);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension(3)->setRowHeight(22);

            // Period
            $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension(4)->setRowHeight(16);

            // Heading rows 5–6: bold, bordered, centered, wrap
            $sheet->getStyle("A5:{$lastCol}6")
                ->getFont()->setBold(true);
            $sheet->getStyle("A5:{$lastCol}6")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getRowDimension(5)->setRowHeight(22);
            $sheet->getRowDimension(6)->setRowHeight(18);
            $this->applyThinBorders($sheet, "A5:{$lastCol}6");

            // Data rows
            $dataStart = 7;
            $dataEnd = $lastDataRow;
            if ($dataStart <= $dataEnd) {
                $sheet->getStyle("A{$dataStart}:{$lastCol}{$dataEnd}")
                    ->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                // Name column: wrap long names, left-aligned
                foreach (self::WRAP_COLS as $wrapCol) {
                    $sheet->getStyle("{$wrapCol}{$dataStart}:{$wrapCol}{$dataEnd}")
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
                // Numeric columns right-aligned, no wrap
                $sheet->getStyle("C{$dataStart}:{$lastCol}{$dataEnd}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setWrapText(false);
                $sheet->getStyle("C{$dataStart}:{$lastCol}{$dataEnd}")
                    ->getNumberFormat()->setFormatCode('#,##0');
                $this->applyThinBorders($sheet, "A{$dataStart}:{$lastCol}{$dataEnd}");
                // Auto row-height so wrapped names expand the row
                for ($r = $dataStart; $r <= $dataEnd; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(-1);
                }
            }

            // Totals row borders & format
            $this->applyThinBorders($sheet, "A{$totalsRow}:{$lastCol}{$totalsRow}");
            $sheet->getStyle("C{$totalsRow}:{$lastCol}{$totalsRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$totalsRow}:{$lastCol}{$totalsRow}")
                ->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getRowDimension($totalsRow)->setRowHeight(-1);

            // ── Column widths ─────────────────────────────────────────────────
            foreach (self::COL_WIDTHS as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            // Signature
            $sheet->getStyle("A{$sigRow}:{$lastCol}{$sigRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$sigRow}:{$lastCol}{$sigRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($sigRow)->setRowHeight(30);

            $sheet->freezePane('A7');

            // ══════════════════════════════════════════════════════════════════
            // P R I N T  /  P A G E  S E T U P
            // ══════════════════════════════════════════════════════════════════
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
            $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
            $pageSetup->setFitToPage(true);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);

            $sheet->getPageSetup()->setPrintArea("A1:{$lastCol}{$sigRow}");
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(5, 6);

            $margins = $sheet->getPageMargins();
            $margins->setTop(0.5);
            $margins->setBottom(0.5);
            $margins->setLeft(0.6);
            $margins->setRight(0.5);
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
