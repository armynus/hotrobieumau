<?php

namespace App\Exports;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class DocumentLedgerExport extends DefaultValueBinder implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithColumnFormatting,
    WithColumnWidths,
    WithCustomChunkSize,
    WithCustomValueBinder,
    WithEvents,
    WithProperties,
    WithStrictNullComparison,
    WithTitle
{
    public function __construct(
        private readonly Builder $documents,
        private readonly string $direction,
        private readonly string $periodLabel,
    ) {
    }

    public function query(): Builder
    {
        return clone $this->documents;
    }

    public function headings(): array
    {
        if ($this->isOutgoing()) {
            return [
                'Ngày tháng chuyển',
                'Số, ký hiệu Văn bản',
                'Người ký văn bản',
                'Ngày, tháng VB',
                'Tên loại và trích yếu nội dung văn bản',
                'Nơi nhận văn bản',
                'Đơn vị, người nhận bản lưu',
                'Số lượng bản',
                'Ký nhận',
                'Ghi chú',
            ];
        }

        return [
            'Ngày tháng đến',
            'Số đến',
            'Tác giả',
            'Số & ký hiệu văn bản',
            'Ngày, tháng văn bản',
            'Tên loại và trích yếu nội dung văn bản',
            'Đơn vị hoặc người nhận',
            'Ngày chuyển',
            'Ký nhận',
            'Ghi chú',
        ];
    }

    public function map($document): array
    {
        if ($this->isOutgoing()) {
            return [
                $this->excelDate($document->forwarded_date ?? $document->issued_date),
                $document->document_code,
                $document->signer,
                $this->excelDate($document->issued_date),
                $document->title,
                $document->recipient,
                $document->archive_recipient,
                $document->copy_count,
                $document->receipt_signature,
                $document->notes,
            ];
        }

        return [
            $this->excelDate($document->received_date ?? $document->issued_date),
            $document->registry_number,
            $document->issuing_agency,
            $document->document_code,
            $this->excelDate($document->issued_date),
            $document->title,
            $document->recipient,
            $this->excelDate($document->forwarded_date),
            $document->receipt_signature,
            $document->notes,
        ];
    }

    public function columnFormats(): array
    {
        return $this->isOutgoing()
            ? ['A' => 'dd/mm/yyyy', 'D' => 'dd/mm/yyyy', 'H' => NumberFormat::FORMAT_NUMBER]
            : ['A' => 'dd/mm/yyyy', 'E' => 'dd/mm/yyyy', 'H' => 'dd/mm/yyyy'];
    }

    public function columnWidths(): array
    {
        if ($this->isOutgoing()) {
            return ['A' => 16, 'B' => 28, 'C' => 24, 'D' => 16, 'E' => 55, 'F' => 30, 'G' => 28, 'H' => 13, 'I' => 18, 'J' => 24];
        }

        return ['A' => 16, 'B' => 11, 'C' => 20, 'D' => 28, 'E' => 16, 'F' => 55, 'G' => 30, 'H' => 16, 'I' => 18, 'J' => 24];
    }

    public function chunkSize(): int
    {
        return max(100, (int) config('documents.exports.chunk_size', 500));
    }

    public function title(): string
    {
        return $this->isOutgoing() ? 'Sổ văn bản đi' : 'Sổ văn bản đến';
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name'),
            'title' => $this->title() . ' - ' . $this->periodLabel,
            'subject' => $this->periodLabel,
            'company' => config('app.name'),
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Văn bản từ người dùng luôn là chuỗi: giữ số 0 đầu dòng và chặn formula injection.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = max(1, $sheet->getHighestDataRow());

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:J{$highestRow}");
                $sheet->setShowGridlines(false);
                $sheet->getRowDimension(1)->setRowHeight(58);
                $sheet->getDefaultRowDimension()->setRowHeight(-1);
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.25)->setRight(0.25);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

                $sheet->getStyle('A1:J1')->applyFromArray([
                    'font' => ['name' => 'Times New Roman', 'size' => 12, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'AE1C3F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7A1630']]],
                ]);

                if ($highestRow > 1) {
                    $sheet->getStyle("A2:J{$highestRow}")->applyFromArray([
                        'font' => ['name' => 'Times New Roman', 'size' => 12, 'color' => ['rgb' => '202124']],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B7BCC5']]],
                    ]);
                    $centerThrough = $this->isOutgoing() ? 'D' : 'E';
                    $sheet->getStyle("A2:{$centerThrough}{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("H2:I{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }

    private function excelDate($date): ?float
    {
        return $date ? Date::dateTimeToExcel($date) : null;
    }

    private function isOutgoing(): bool
    {
        return $this->direction === Document::DIRECTION_OUTGOING;
    }
}
