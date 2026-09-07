<?php

namespace App\Services;

use App\Models\Document;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class DocumentLedgerReader
{
    private const HEADER_SCAN_ROWS = 50;

    private const HEADER_SCAN_COLUMNS = 30;

    private const MAX_LEDGER_ROWS_PER_SHEET = 100000;

    private const INCOMING_HEADERS = [
        'received_date' => ['ngay thang den', 'ngay den'],
        'registry_number' => ['so den'],
        'issuing_agency' => ['tac gia', 'co quan gui', 'don vi gui'],
        'document_code' => ['so va ky hieu van ban', 'so ky hieu van ban'],
        'issued_date' => ['ngay thang van ban', 'ngay van ban'],
        'title' => ['ten loai va trich yeu noi dung van ban', 'trich yeu noi dung van ban', 'trich yeu'],
        'recipient' => ['don vi hoac nguoi nhan', 'don vi nguoi nhan'],
        'forwarded_date' => ['ngay chuyen'],
        'receipt_signature' => ['ky nhan'],
        'notes' => ['ghi chu'],
    ];

    private const OUTGOING_HEADERS = [
        'forwarded_date' => ['ngay thang chuyen', 'ngay chuyen'],
        'document_code' => ['so ky hieu van ban', 'so va ky hieu van ban'],
        'signer' => ['nguoi ky van ban', 'nguoi ky'],
        'issued_date' => ['ngay thang vb', 'ngay thang van ban', 'ngay van ban'],
        'title' => ['ten loai va trich yeu noi dung van ban', 'trich yeu noi dung van ban', 'trich yeu'],
        'recipient' => ['noi nhan van ban', 'noi nhan'],
        'archive_recipient' => ['don vi nguoi nhan ban luu', 'nguoi nhan ban luu'],
        'copy_count' => ['so luong ban', 'so ban'],
        'receipt_signature' => ['ky nhan'],
        'notes' => ['ghi chu'],
    ];

    /**
     * Đọc mọi sheet có đúng cấu trúc sổ và trả về dữ liệu đã chuẩn hóa.
     *
     * @return array<int, array<string, mixed>>
     */
    public function read(string $path, string $direction): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Không đọc được file sổ Excel: '.$path);
        }

        if (! in_array($direction, [Document::DIRECTION_INCOMING, Document::DIRECTION_OUTGOING], true)) {
            throw new RuntimeException('Loại sổ chỉ nhận incoming hoặc outgoing.');
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $worksheetInfo = collect($reader->listWorksheetInfo($path))
                ->keyBy('worksheetName');

            // Lần đọc đầu chỉ nạp 50 dòng/30 cột đầu để tìm đúng các sheet sổ.
            // Điều này tránh kéo theo những sheet phụ có hàng trăm nghìn ô định dạng rỗng.
            $reader->setReadFilter(new DocumentLedgerReadFilter(
                null,
                1,
                self::HEADER_SCAN_ROWS,
                range(1, self::HEADER_SCAN_COLUMNS),
            ));
            $headerSpreadsheet = $reader->load($path);
            $ledgers = [];

            foreach ($headerSpreadsheet->getWorksheetIterator() as $worksheet) {
                $header = $this->findHeader($worksheet, $direction);
                if ($header !== null) {
                    $ledgers[$worksheet->getTitle()] = $header;
                }
            }

            $headerSpreadsheet->disconnectWorksheets();
            unset($headerSpreadsheet);

            $rows = [];
            foreach ($ledgers as $worksheetName => $header) {
                $highestRow = (int) ($worksheetInfo->get($worksheetName)['totalRows'] ?? 0);
                if ($highestRow > self::MAX_LEDGER_ROWS_PER_SHEET) {
                    throw new RuntimeException(
                        'Sheet "'.$worksheetName.'" vượt quá '.number_format(self::MAX_LEDGER_ROWS_PER_SHEET)
                        .' dòng. Hãy xóa các dòng định dạng rỗng cuối sheet trước khi nhập.'
                    );
                }

                if ($highestRow <= $header['row']) {
                    continue;
                }

                // Mỗi lần chỉ nạp một sheet và đúng các cột nghiệp vụ đã nhận diện.
                // Nhờ vậy bộ nhớ không tăng theo toàn bộ workbook nhiều sheet.
                $sheetReader = IOFactory::createReaderForFile($path);
                $sheetReader->setReadDataOnly(true);
                $sheetReader->setLoadSheetsOnly($worksheetName);
                $sheetReader->setReadFilter(new DocumentLedgerReadFilter(
                    $worksheetName,
                    $header['row'] + 1,
                    $highestRow,
                    array_values($header['columns']),
                ));
                $spreadsheet = $sheetReader->load($path);
                $worksheet = $spreadsheet->getSheetByName($worksheetName);

                if ($worksheet === null) {
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet);

                    continue;
                }

                for ($rowNumber = $header['row'] + 1; $rowNumber <= $highestRow; $rowNumber++) {
                    $row = [
                        'direction' => $direction,
                        '_sheet' => $worksheet->getTitle(),
                        '_row' => $rowNumber,
                    ];

                    foreach ($header['columns'] as $field => $column) {
                        $cell = $worksheet->getCell([$column, $rowNumber]);
                        $row[$field] = in_array($field, ['received_date', 'issued_date', 'forwarded_date'], true)
                            ? $this->dateValue($cell)
                            : ($field === 'copy_count' ? $this->integerValue($cell) : $this->textValue($cell));
                    }

                    if (($row['document_code'] ?? null) === null && ($row['title'] ?? null) === null) {
                        continue;
                    }

                    $rows[] = $row;
                }

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Không thể mở file sổ Excel: '.Str::limit($exception->getMessage(), 600),
                0,
                $exception,
            );
        }

        if ($rows === []) {
            throw new RuntimeException('Không tìm thấy sheet có tiêu đề cột đúng cấu trúc sổ văn bản '.($direction === Document::DIRECTION_OUTGOING ? 'đi.' : 'đến.'));
        }

        return $rows;
    }

    /**
     * @return array{row:int, columns:array<string, int>}|null
     */
    private function findHeader($worksheet, string $direction): ?array
    {
        $aliases = $direction === Document::DIRECTION_OUTGOING
            ? self::OUTGOING_HEADERS
            : self::INCOMING_HEADERS;
        $highestColumnIndex = min(
            30,
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestDataColumn())
        );
        $best = null;

        for ($row = 1; $row <= min(50, $worksheet->getHighestDataRow()); $row++) {
            $columns = [];
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $headerValue = $this->normalizeHeader($this->textValue($worksheet->getCell([$column, $row])));
                if ($headerValue === '') {
                    continue;
                }

                foreach ($aliases as $field => $fieldAliases) {
                    if (! isset($columns[$field]) && in_array($headerValue, array_map([$this, 'normalizeHeader'], $fieldAliases), true)) {
                        $columns[$field] = $column;
                        break;
                    }
                }
            }

            if (isset($columns['document_code'], $columns['title']) && count($columns) >= 5) {
                if ($best === null || count($columns) > count($best['columns'])) {
                    $best = ['row' => $row, 'columns' => $columns];
                }
            }
        }

        return $best;
    }

    private function normalizeHeader(?string $value): string
    {
        $value = mb_strtolower(Str::ascii((string) $value), 'UTF-8');

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '');
    }

    private function rawValue(Cell $cell): mixed
    {
        try {
            return $cell->isFormula() ? $cell->getCalculatedValue() : $cell->getValue();
        } catch (Throwable) {
            return $cell->getValue();
        }
    }

    private function textValue(Cell $cell): ?string
    {
        $value = $this->rawValue($cell);
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        $value = str_replace(["\r\n", "\r"], "\n", (string) $value);
        $lines = array_map(
            fn (string $line) => trim(preg_replace('/^[\s_–—•-]+/u', '', $line) ?? ''),
            explode("\n", $value)
        );
        $value = trim(implode("\n", array_filter($lines, fn (string $line) => $line !== '')));

        return $value !== '' ? $value : null;
    }

    private function integerValue(Cell $cell): ?int
    {
        $value = $this->rawValue($cell);
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function dateValue(Cell $cell): ?string
    {
        $value = $this->rawValue($cell);
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->format('Y-m-d');
        }

        if (is_numeric($value) && (float) $value > 0) {
            try {
                return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y', 'd-m-y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $value);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
