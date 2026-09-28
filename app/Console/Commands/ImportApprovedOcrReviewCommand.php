<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentLog;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class ImportApprovedOcrReviewCommand extends Command
{
    protected $signature = 'ocr:import-approved-review
        {file : Workbook path relative to storage/app/private}
        {--commit : Write approved, still-missing fields to the database; default is dry-run}';

    protected $description = 'Preview or apply only OCR rows explicitly marked OK after review';

    private const HEADERS = [
        'A' => 'ID văn bản',
        'B' => 'Số/ký hiệu đọc được',
        'C' => 'Ngày văn bản đọc được',
        'D' => 'Nơi gởi đọc được',
        'E' => 'Trích yếu đọc được',
        'I' => 'Số/ký hiệu hiện tại',
        'J' => 'Ngày văn bản hiện tại',
        'K' => 'Trích yếu hiện tại',
        'L' => 'Nơi gởi hiện tại',
        'Q' => 'Kiểm tra của bạn',
        'R' => 'Phân loại đề xuất',
        'S' => 'Phân loại hiện tại',
        'T' => 'Căn cứ phân loại',
        'U' => 'Duyệt phân loại',
    ];

    private const FIELDS = [
        'document_code' => 'B',
        'issued_date' => 'C',
        'issuing_agency' => 'D',
        'title' => 'E',
        'direction' => 'R',
    ];

    private const SNAPSHOT = [
        'document_code' => 'I',
        'issued_date' => 'J',
        'title' => 'K',
        'issuing_agency' => 'L',
        'direction' => 'S',
    ];

    public function handle(): int
    {
        $file = str_replace('\\', '/', (string) $this->argument('file'));
        if (! preg_match('/\.xlsx$/i', $file) || preg_match('~(^/|^[a-z]:|(^|/)\.\.(/|$)|[\x00-\x1f])~i', $file)) {
            $this->error('Đường dẫn phải là file .xlsx tương đối trong storage/app/private.');

            return self::INVALID;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($file)) {
            $this->error('Không tìm thấy workbook: '.$disk->path($file));

            return self::FAILURE;
        }

        try {
            $sheet = IOFactory::load($disk->path($file))->getSheetByName('Kiem tra PDF');
            if (! $sheet instanceof Worksheet) {
                throw new RuntimeException('Workbook không có sheet “Kiem tra PDF”.');
            }
            $this->validateHeaders($sheet);
            $approved = $this->approvedRows($sheet);
        } catch (Throwable $e) {
            $this->error('Không đọc được workbook: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($approved['errors'] !== []) {
            $this->error('Có dòng được đánh OK nhưng không hợp lệ:');
            foreach (array_slice($approved['errors'], 0, 30) as $error) {
                $this->line(' - '.$error);
            }

            return self::FAILURE;
        }
        if ($approved['rows'] === []) {
            $this->info('Không có dòng nào được đánh OK ở cột “Kiểm tra của bạn”. Database chưa thay đổi.');

            return self::SUCCESS;
        }

        $byId = [];
        foreach ($approved['rows'] as $row) {
            $byId[$row['id']][] = $row;
        }
        $duplicates = array_filter($byId, fn (array $rows) => count($rows) > 1);
        $reports = [];
        foreach ($duplicates as $id => $rows) {
            foreach ($rows as $row) {
                $reports[] = [$id, $row['excel_row'], 'Bỏ qua: nhiều PDF cùng ID được duyệt'];
            }
            unset($byId[$id]);
        }

        $commit = (bool) $this->option('commit');
        $processor = function () use ($byId, &$reports, $commit, $file): void {
            foreach ($byId as $id => $rows) {
                $row = $rows[0];
                $query = Document::query()->whereKey($id);
                if ($commit) {
                    $query->lockForUpdate();
                }
                $document = $query->first();
                if (! $document) {
                    $reports[] = [$id, $row['excel_row'], 'Bỏ qua: không tìm thấy văn bản'];

                    continue;
                }
                if (! $this->matchesSnapshot($document, $row['snapshot'])) {
                    $reports[] = [$id, $row['excel_row'], 'Bỏ qua: dữ liệu hiện tại đã đổi từ lúc xuất Excel'];

                    continue;
                }

                $updates = [];
                foreach (array_diff_key($row['values'], ['direction' => true]) as $field => $value) {
                    if ($row['metadata_approved'] && $value !== null && blank($document->getAttribute($field))) {
                        $updates[$field] = $value;
                    }
                }
                $suggestedDirection = $row['values']['direction'];
                if ($row['direction_approved'] && $suggestedDirection !== null && $document->direction !== $suggestedDirection) {
                    $updates['direction'] = $suggestedDirection;
                }
                if ($updates === []) {
                    $reports[] = [$id, $row['excel_row'], 'Không đổi: không có trường được duyệt cần cập nhật'];

                    continue;
                }

                if ($commit) {
                    $before = array_intersect_key($document->getAttributes(), $updates);
                    $document->forceFill($updates)->save();
                    DocumentLog::create([
                        'document_id' => $document->id,
                        'user_id' => null,
                        'action' => 'metadata_updated_from_ocr_review',
                        'details' => [
                            'source' => 'ocr-review-excel',
                            'file' => $file,
                            'excel_row' => $row['excel_row'],
                            'updated_fields' => array_keys($updates),
                            'before' => $before,
                            'after' => $updates,
                        ],
                    ]);
                }
                $labels = ['document_code' => 'Số/ký hiệu', 'issued_date' => 'Ngày văn bản',
                    'issuing_agency' => 'Nơi gởi', 'title' => 'Trích yếu'];
                $changed = array_map(fn ($field) => $field === 'direction'
                    ? 'Phân loại: '.match ($updates[$field]) {
                        Document::DIRECTION_INCOMING => 'Văn bản đến',
                        Document::DIRECTION_OUTGOING => 'Văn bản đi',
                        Document::DIRECTION_DECISION => 'Quyết định',
                        default => 'Chưa phân loại',
                    }
                    : $labels[$field], array_keys($updates));
                $reports[] = [$id, $row['excel_row'], ($commit ? 'Đã cập nhật: ' : 'Dự kiến điền: ').implode(', ', $changed)];
            }
        };

        try {
            if ($commit) {
                DB::transaction($processor, 3);
            } else {
                $processor();
            }
        } catch (Throwable $e) {
            $this->error(($commit ? 'Không hoàn tất cập nhật; giao dịch đã được hoàn tác: ' : 'Không chạy được bước xem trước: ').$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['ID', 'Dòng Excel', 'Kết quả'], $reports);
        $this->line('Số dòng được duyệt ở Q hoặc U: '.count($approved['rows']).'; số dòng bỏ qua vì ID PDF trùng: '.array_sum(array_map('count', $duplicates)).'.');
        $updated = count(array_filter($reports, fn (array $report) => str_starts_with($report[2], 'Đã cập nhật: ')));
        if (! $commit) {
            $this->info('Chạy thử xong; database chưa thay đổi. Thêm --commit để áp dụng các dòng này.');
        } else {
            $this->info($updated > 0
                ? "Đã cập nhật {$updated} văn bản và lưu nhật ký thay đổi."
                : 'Không có dữ liệu nào cần cập nhật.');
        }

        return self::SUCCESS;
    }

    private function validateHeaders(Worksheet $sheet): void
    {
        foreach (self::HEADERS as $column => $expected) {
            if ((string) $sheet->getCell($column.'1')->getValue() !== $expected) {
                throw new RuntimeException("Cột {$column}1 phải có tiêu đề “{$expected}”; hãy dùng workbook xuất từ tool này.");
            }
        }
    }

    private function approvedRows(Worksheet $sheet): array
    {
        $rows = [];
        $errors = [];
        $highest = $sheet->getHighestDataRow();
        for ($number = 2; $number <= $highest; $number++) {
            $metadataApproval = $sheet->getCell('Q'.$number);
            $directionApproval = $sheet->getCell('U'.$number);
            $metadataApproved = $metadataApproval->getDataType() !== DataType::TYPE_FORMULA
                && mb_strtoupper(trim((string) $metadataApproval->getValue())) === 'OK';
            $directionApproved = $directionApproval->getDataType() !== DataType::TYPE_FORMULA
                && mb_strtoupper(trim((string) $directionApproval->getValue())) === 'OK';
            if (! $metadataApproved && ! $directionApproved) {
                continue;
            }
            try {
                foreach (['A', 'B', 'C', 'D', 'E', 'I', 'J', 'K', 'L', 'Q', 'R', 'S', 'U'] as $column) {
                    if ($sheet->getCell($column.$number)->getDataType() === DataType::TYPE_FORMULA) {
                        throw new RuntimeException("cột {$column} chứa công thức");
                    }
                }
                $rawId = $sheet->getCell('A'.$number)->getValue();
                if (! is_numeric($rawId) || (int) $rawId < 1 || (float) $rawId !== (float) (int) $rawId) {
                    throw new RuntimeException('ID văn bản không hợp lệ');
                }
                $values = [];
                foreach (self::FIELDS as $field => $column) {
                    $values[$field] = match ($field) {
                        'issued_date' => $this->dateCell($sheet, $column, $number),
                        'direction' => $this->directionCell($sheet, $column, $number, false),
                        default => $this->textCell($sheet, $column, $number),
                    };
                }
                $snapshot = [];
                foreach (self::SNAPSHOT as $field => $column) {
                    $snapshot[$field] = match ($field) {
                        'issued_date' => $this->dateCell($sheet, $column, $number),
                        'direction' => $this->directionCell($sheet, $column, $number, true),
                        default => $this->textCell($sheet, $column, $number),
                    };
                }
                if (mb_strlen((string) $values['document_code']) > 255 || mb_strlen((string) $values['issuing_agency']) > 255) {
                    throw new RuntimeException('số/ký hiệu hoặc nơi gởi dài quá 255 ký tự');
                }
                $rows[] = ['id' => (int) $rawId, 'excel_row' => $number, 'values' => $values, 'snapshot' => $snapshot,
                    'metadata_approved' => $metadataApproved, 'direction_approved' => $directionApproved];
            } catch (Throwable $e) {
                $errors[] = "Dòng {$number}: {$e->getMessage()}";
            }
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    private function textCell(Worksheet $sheet, string $column, int $row): ?string
    {
        $value = $sheet->getCell($column.$row)->getValue();
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function dateCell(Worksheet $sheet, string $column, int $row): ?string
    {
        $cell = $sheet->getCell($column.$row);
        $value = $cell->getValue();
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if ($cell->getDataType() === DataType::TYPE_NUMERIC && is_numeric($value)) {
            $date = Date::excelToDateTimeObject((float) $value);
            if ((int) $date->format('Y') < 1900 || (int) $date->format('Y') > 2200) {
                throw new RuntimeException("ngày ở cột {$column} không hợp lệ");
            }

            return $date->format('Y-m-d');
        }

        foreach (['!Y-m-d', '!d/m/Y', '!d-m-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, trim((string) $value));
            $issues = DateTimeImmutable::getLastErrors();
            if ($date && ($issues === false || ($issues['warning_count'] === 0 && $issues['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        throw new RuntimeException("ngày ở cột {$column} phải là ngày Excel hợp lệ");
    }

    private function directionCell(Worksheet $sheet, string $column, int $row, bool $snapshot): ?string
    {
        $value = mb_strtolower(trim((string) $sheet->getCell($column.$row)->getValue()));
        $directions = [
            'văn bản đến' => Document::DIRECTION_INCOMING,
            'văn bản đi' => Document::DIRECTION_OUTGOING,
            'quyết định' => Document::DIRECTION_DECISION,
            'chưa phân loại' => Document::DIRECTION_UNCLASSIFIED,
        ];
        if (isset($directions[$value])) {
            return $directions[$value];
        }
        if ($value === '' || (! $snapshot && $value === 'chưa đủ căn cứ')) {
            return null;
        }

        throw new RuntimeException("phân loại ở cột {$column} không hợp lệ");
    }

    private function matchesSnapshot(Document $document, array $snapshot): bool
    {
        foreach ($snapshot as $field => $value) {
            $current = $document->getAttribute($field);
            if ($field === 'issued_date' && $current !== null) {
                $current = $current->format('Y-m-d');
            }
            if ($this->normalize($current) !== $this->normalize($value)) {
                return false;
            }
        }

        return true;
    }

    private function normalize(mixed $value): ?string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', (string) ($value ?? '')) ?? '');

        return $normalized === '' ? null : $normalized;
    }
}
