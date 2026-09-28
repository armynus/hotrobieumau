<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OcrReviewSheet extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithEvents, WithHeadings, WithTitle
{
    public function __construct(private readonly array $rows, private readonly bool $evidence = false) {}

    public function title(): string
    {
        return $this->evidence ? 'Chu doc tu PDF' : 'Kiem tra PDF';
    }

    public function headings(): array
    {
        return $this->evidence
            ? ['ID văn bản', 'Tên PDF', 'Trang', 'Vùng đọc', 'Chữ đọc được', 'Đường dẫn lưu trữ', 'SHA256']
            : ['ID văn bản', 'Số/ký hiệu đọc được', 'Ngày văn bản đọc được', 'Nơi gởi đọc được', 'Trích yếu đọc được',
                'Dữ liệu còn thiếu', 'Kết quả quét', 'Lưu ý đối chiếu', 'Số/ký hiệu hiện tại', 'Ngày văn bản hiện tại',
                'Trích yếu hiện tại', 'Nơi gởi hiện tại', 'Tên PDF', 'Trang', 'Vùng đọc', 'Mở PDF', 'Kiểm tra của bạn',
                'Phân loại đề xuất', 'Phân loại hiện tại', 'Căn cứ phân loại', 'Duyệt phân loại'];
    }

    public function array(): array
    {
        return array_map(function (array $r) {
            if ($this->evidence) {
                return [$r['id'], $r['file'] ?? '', $r['page'] ?? '', $r['region'] ?? '', mb_substr($r['text'] ?? '', 0, 32000), $r['path'] ?? '', $r['sha256'] ?? ''];
            }

            return [$r['id'], $r['code'] ?? '', $this->date($r['date'] ?? null), $r['agency'] ?? '', $r['title'] ?? '',
                $r['missing'], $r['status'], $r['warning'] ?? '', $r['current_code'], $this->date($r['current_date']),
                $r['current_title'], $r['current_agency'], $r['file'] ?? '', $r['page'] ?? '', $r['region'] ?? '', $r['url'] ?? '', '',
                $this->directionLabel($r['direction'] ?? null), $this->directionLabel($r['current_direction'] ?? null, false), $r['direction_reason'] ?? '', ''];
        }, $this->rowsForSheet());
    }

    private function directionLabel(?string $direction, bool $suggestion = true): string
    {
        return match ($direction) {
            'incoming' => 'Văn bản đến',
            'outgoing' => 'Văn bản đi',
            'decision' => 'Quyết định',
            'unclassified' => 'Chưa phân loại',
            default => $suggestion ? 'Chưa đủ căn cứ' : 'Chưa phân loại',
        };
    }

    private function rowsForSheet(): array
    {
        if (! $this->evidence) {
            return $this->rows;
        }
        $rows = [];
        foreach ($this->rows as $row) {
            $lines = [];
            foreach (preg_split('/\R/u', $row['text'] ?? '') ?: [''] as $line) {
                array_push($lines, ...explode("\n", wordwrap($line, 110, "\n")));
            }
            $parts = array_chunk($lines, 15);
            foreach ($parts as $index => $part) {
                $record = $row;
                $record['text'] = implode("\n", $part);
                if (count($parts) > 1) {
                    $record['region'] = ($row['region'] ?? '').' (đoạn '.($index + 1).'/'.count($parts).')';
                }
                $rows[] = $record;
            }
        }

        return $rows;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    private function date(?string $value): float|string
    {
        return $value ? Date::PHPToExcel(new \DateTimeImmutable($value)) : '';
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $rows = $this->rowsForSheet();
            $last = count($rows) + 1;
            $end = $this->evidence ? 'G' : 'U';
            $sheet->setShowGridlines(false);
            $sheet->freezePane('C2');
            $sheet->setAutoFilter("A1:{$end}{$last}");
            $sheet->getStyle("A1:{$end}{$last}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 11], 'alignment' => ['vertical' => 'top', 'wrapText' => true],
            ]);
            $sheet->getStyle("A1:{$end}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '743B47']],
                'alignment' => ['vertical' => 'center', 'horizontal' => 'center'],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(44);
            if ($last > 1) {
                $sheet->getStyle("A2:A{$last}")->getAlignment()->setHorizontal('center');
                foreach ($this->evidence ? ['C'] : ['C', 'J', 'N'] as $column) {
                    $sheet->getStyle("{$column}2:{$column}{$last}")->getAlignment()->setHorizontal('center');
                }
            }
            $widths = $this->evidence ? ['A' => 12, 'B' => 34, 'C' => 10, 'D' => 24, 'E' => 100, 'F' => 48, 'G' => 30]
                : ['A' => 12, 'B' => 28, 'C' => 18, 'D' => 24, 'E' => 65, 'F' => 22, 'G' => 30, 'H' => 44,
                    'I' => 28, 'J' => 18, 'K' => 60, 'L' => 24, 'M' => 36, 'N' => 8, 'O' => 23, 'P' => 32, 'Q' => 40,
                    'R' => 21, 'S' => 21, 'T' => 58, 'U' => 20];
            foreach ($widths as $column => $width) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
            if (! $this->evidence && $last > 1) {
                foreach (['C', 'J'] as $column) {
                    $sheet->getStyle("{$column}2:{$column}{$last}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                }
                $sheet->getStyle("R2:S{$last}")->getAlignment()->setHorizontal('center');
                foreach (['Q', 'U'] as $column) {
                    $sheet->getStyle("{$column}2:{$column}{$last}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
                }
            }
            foreach ($rows as $index => $row) {
                $n = $index + 2;
                $lineCount = $this->evidence ? substr_count($row['text'] ?? '', "\n") + 1 : max(
                    ceil(mb_strlen($row['title'] ?? '') / 55),
                    ceil(mb_strlen($row['current_title'] ?? '') / 50),
                    ceil(mb_strlen($row['warning'] ?? '') / 38),
                    ceil(mb_strlen($row['direction_reason'] ?? '') / 45),
                    ceil(mb_strlen($row['file'] ?? '') / 30),
                );
                $sheet->getRowDimension($n)->setRowHeight(min(390, max(78, $lineCount * 18 + 12)));
                if (! $this->evidence && ! empty($row['url'])) {
                    $sheet->getCell("P{$n}")->getHyperlink()->setUrl($row['url']);
                }
                if ($n % 2 === 0) {
                    $sheet->getStyle("A{$n}:".($this->evidence ? 'G' : 'P').$n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F7F5F2');
                    if (! $this->evidence) {
                        $sheet->getStyle("R{$n}:T{$n}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F7F5F2');
                    }
                }
            }
        }];
    }
}
