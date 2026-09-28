<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentLog;
use App\Services\DocumentReviewDirectionClassifier;
use App\Services\PdfDocumentReviewScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SyncDocumentMetadataFromPdfCommand extends Command
{
    protected $signature = 'ocr:sync-from-pdf
        {--limit=100 : Số văn bản tối đa; 0 để xử lý hết phạm vi}
        {--id=* : Chỉ xử lý các ID này}
        {--branch= : Giới hạn chi nhánh sở hữu}
        {--after-id=0 : Chỉ xử lý ID lớn hơn giá trị này}
        {--dry-run : Chỉ xem trước, không cập nhật database}';

    protected $description = 'Tự đồng bộ metadata văn bản từ PDF theo quy tắc bảo vệ dữ liệu đã có';

    private const FIELDS = ['issued_date', 'issuing_agency', 'document_code', 'title', 'direction'];

    public function handle(PdfDocumentReviewScanner $scanner, DocumentReviewDirectionClassifier $classifier): int
    {
        foreach (['limit', 'after-id'] as $option) {
            if (! ctype_digit((string) $this->option($option))) {
                $this->error("--{$option} phải là số nguyên không âm.");

                return self::INVALID;
            }
        }

        $ids = $this->option('id');
        foreach (array_merge($ids, $this->option('branch') !== null ? [$this->option('branch')] : []) as $id) {
            if (! ctype_digit((string) $id) || (int) $id < 1) {
                $this->error('ID và chi nhánh phải là số nguyên dương.');

                return self::INVALID;
            }
        }

        $pdfFilter = fn ($query) => $query->where(fn ($pdf) => $pdf
            ->whereRaw('LOWER(file_extension) = ?', ['pdf'])
            ->orWhereRaw('LOWER(file_name) LIKE ?', ['%.pdf']));
        $query = Document::query()
            ->whereHas('attachments', $pdfFilter)
            ->with(['attachments' => fn ($attachments) => $pdfFilter($attachments)->orderBy('id')])
            ->where('id', '>', (int) $this->option('after-id'))
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))
            ->when($this->option('branch') !== null, fn ($q) => $q->where('managing_branch_id', (int) $this->option('branch')))
            ->orderBy('id');

        $total = (clone $query)->count();
        $limit = (int) $this->option('limit');
        $total = $limit > 0 ? min($limit, $total) : $total;
        if ($total === 0) {
            $this->info('Không có văn bản kèm PDF trong phạm vi đã chọn.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? 'Xem trước ' : 'Tự cập nhật ')."{$total} văn bản có PDF. Không cần duyệt từng dòng OK.");
        $stats = ['updated' => 0, 'unchanged' => 0, 'conflicts' => 0, 'errors' => 0];
        $processed = 0;
        $examples = [];

        foreach ($query->lazyById(50) as $document) {
            if ($processed >= $total) {
                break;
            }
            $processed++;
            try {
                $result = $this->scanAttachments($document, $scanner, $classifier);
                if ($result['error'] !== null) {
                    $stats['errors']++;
                    $examples[] = [$document->id, 'Lỗi đọc PDF: '.$result['error']];
                } else {
                    $status = $this->applyResult($document, $result, $dryRun);
                    $stats[$status['kind']]++;
                    if ($status['has_conflicts']) {
                        $stats['conflicts']++;
                    }
                    if ($status['message'] !== '') {
                        $examples[] = [$document->id, $status['message']];
                    }
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                $examples[] = [$document->id, 'Lỗi: '.mb_substr($e->getMessage(), 0, 300)];
            }

            if ($processed % 25 === 0 || $processed === $total) {
                $this->line("{$processed}/{$total} (ID {$document->id})");
            }
        }

        if ($examples !== []) {
            $this->table(['ID', 'Kết quả / ví dụ'], array_slice($examples, 0, 50));
            if (count($examples) > 50) {
                $this->line('Còn '.(count($examples) - 50).' dòng khác; xem nhật ký tiến trình hoặc chạy theo lô ID nhỏ hơn.');
            }
        }
        $this->table(['Đã quét', 'Đã cập nhật', 'Không đổi / thiếu dấu hiệu', 'Xung đột PDF', 'Lỗi'], [[
            $processed, $stats['updated'], $stats['unchanged'], $stats['conflicts'], $stats['errors'],
        ]]);

        if ($dryRun) {
            $this->info('Database chưa thay đổi. Bỏ --dry-run để cập nhật tự động.');
        } else {
            $this->info("Hoàn tất. Các thay đổi đã được ghi nhật ký cho {$stats['updated']} văn bản.");
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function scanAttachments(Document $document, PdfDocumentReviewScanner $scanner, DocumentReviewDirectionClassifier $classifier): array
    {
        $sources = [];
        $scans = [];
        foreach ($document->attachments as $attachment) {
            if (! Storage::disk('public')->exists($attachment->file_path)) {
                return ['error' => "không tìm thấy {$attachment->file_name}"];
            }

            $result = $scanner->scan(Storage::disk('public')->path($attachment->file_path));
            $result['auto_title_update_allowed'] = $this->titleIsSafeForAutomaticUpdate($result);
            $sources[] = [
                'attachment_id' => $attachment->id,
                'file' => $attachment->file_name,
                'sha256' => $result['sha256'] ?? null,
            ];
            $result['classification'] = $classifier->classify($result['text'] ?? '', $result['metadata'] ?? []);
            $scans[] = $result;
        }

        return ['error' => null, 'sources' => $sources, 'scans' => $scans, 'resolved' => $this->resolveFields($scans)];
    }

    private function resolveFields(array $scans): array
    {
        $resolved = [];
        $conflicts = [];
        $titleSkippedForReview = false;
        foreach (['issued_date', 'issuing_agency', 'document_code', 'title'] as $field) {
            $values = array_map(function ($scan) use ($field, &$titleSkippedForReview) {
                if ($field === 'title' && ! ($scan['auto_title_update_allowed'] ?? false)) {
                    $titleSkippedForReview = $titleSkippedForReview || filled($scan['metadata']['title'] ?? null);

                    return null;
                }

                return $scan['metadata'][$field] ?? null;
            }, $scans);
            [$resolved[$field], $conflicts[$field]] = $this->resolveValues($values);
        }
        $directions = array_map(fn ($scan) => $scan['classification']['direction'] ?? null, $scans);
        [$resolved['direction'], $conflicts['direction']] = $this->resolveValues($directions);

        return ['values' => $resolved, 'conflicts' => array_keys(array_filter($conflicts)), 'title_skipped_for_review' => $titleSkippedForReview];
    }

    private function titleIsSafeForAutomaticUpdate(array $scan): bool
    {
        if (blank($scan['metadata']['title'] ?? null)
            || ! in_array($scan['region'] ?? null, ['Lớp chữ PDF', 'Ô nội dung trình', 'Toàn trang'], true)) {
            return false;
        }

        foreach ($scan['metadata']['warnings'] ?? [] as $warning) {
            if (str_contains($warning, 'Trích yếu đọc từ mục')
                || str_contains($warning, 'Trích yếu quá')
                || str_contains($warning, 'dấu hiệu OCR nhầm')
                || str_contains($warning, 'bị cắt ở cuối')) {
                return false;
            }
        }

        return true;
    }

    private function resolveValues(array $values): array
    {
        $unique = [];
        foreach ($values as $value) {
            if ($value === null || trim((string) $value) === '') {
                continue;
            }
            $key = $this->normalize($value);
            $unique[$key] ??= trim((string) $value);
        }

        if (count($unique) > 1) {
            return [null, true];
        }

        return [array_values($unique)[0] ?? null, false];
    }

    private function applyResult(Document $document, array $result, bool $dryRun): array
    {
        $snapshot = $this->snapshot($document);
        $processor = function () use ($document, $result, $snapshot, $dryRun): array {
            $current = Document::query()->whereKey($document->id)->when(! $dryRun, fn ($q) => $q->lockForUpdate())->first();
            if (! $current) {
                return ['kind' => 'unchanged', 'has_conflicts' => false, 'message' => 'bỏ qua: văn bản đã bị xóa'];
            }
            if ($this->snapshot($current) !== $snapshot) {
                return ['kind' => 'unchanged', 'has_conflicts' => true, 'message' => 'bỏ qua: dữ liệu database đã đổi trong lúc quét'];
            }

            $values = $result['resolved']['values'];
            $conflicts = $result['resolved']['conflicts'];
            $updates = [];
            foreach (['issued_date', 'issuing_agency'] as $field) {
                if (! in_array($field, $conflicts, true) && $values[$field] !== null
                    && ! $this->sameFieldValue($field, $current->getAttribute($field), $values[$field])) {
                    $updates[$field] = $values[$field];
                }
            }
            foreach (['document_code', 'title'] as $field) {
                if (! in_array($field, $conflicts, true) && blank($current->getAttribute($field)) && $values[$field] !== null) {
                    $updates[$field] = $values[$field];
                }
            }
            if (! in_array('direction', $conflicts, true)
                && in_array($current->direction, [null, '', Document::DIRECTION_UNCLASSIFIED], true)
                && $values['direction'] !== null) {
                $updates['direction'] = $values['direction'];
            }

            foreach (['document_code', 'issuing_agency'] as $field) {
                if (isset($updates[$field]) && mb_strlen((string) $updates[$field]) > 255) {
                    unset($updates[$field]);
                }
            }

            if ($updates === []) {
                $hasConflicts = $conflicts !== [];

                return ['kind' => 'unchanged', 'has_conflicts' => $hasConflicts,
                    'message' => ($hasConflicts ? 'bỏ qua trường xung đột giữa nhiều PDF: '.implode(', ', $conflicts) : 'không có trường cần cập nhật')
                        .($result['resolved']['title_skipped_for_review'] ? '; bỏ qua trích yếu OCR cần rà soát' : '')];
            }

            if (! $dryRun) {
                $before = array_intersect_key($current->getAttributes(), $updates);
                $current->forceFill($updates)->save();
                DocumentLog::create([
                    'document_id' => $current->id,
                    'user_id' => null,
                    'action' => 'metadata_auto_updated_from_pdf',
                    'details' => [
                        'source' => 'ocr-pdf-auto-sync',
                        'updated_fields' => array_keys($updates),
                        'before' => $before,
                        'after' => $updates,
                        'pdfs' => $result['sources'],
                        'conflicting_fields_skipped' => $conflicts,
                        'fields_skipped_for_review' => $result['resolved']['title_skipped_for_review'] ? ['title'] : [],
                    ],
                ]);
            }

            $labels = ['issued_date' => 'Ngày văn bản', 'issuing_agency' => 'Nơi gởi', 'document_code' => 'Số/ký hiệu',
                'title' => 'Trích yếu', 'direction' => 'Phân loại'];

            return ['kind' => 'updated', 'has_conflicts' => $conflicts !== [],
                'message' => 'cập nhật '.implode(', ', array_map(fn ($field) => $labels[$field], array_keys($updates)))
                    .($conflicts !== [] ? '; bỏ qua PDF xung đột ở '.implode(', ', $conflicts) : '')
                    .($result['resolved']['title_skipped_for_review'] ? '; bỏ qua trích yếu OCR cần rà soát' : '')];
        };

        return $dryRun ? $processor() : DB::transaction($processor, 3);
    }

    private function snapshot(Document $document): array
    {
        $snapshot = [];
        foreach (self::FIELDS as $field) {
            $value = $document->getAttribute($field);
            $snapshot[$field] = $field === 'issued_date' && $value !== null ? $value->format('Y-m-d') : $this->normalize($value);
        }

        return $snapshot;
    }

    private function normalize(mixed $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) ($value ?? '')) ?? ''));
    }

    private function sameFieldValue(string $field, mixed $current, mixed $scanned): bool
    {
        if ($field === 'issued_date' && $current !== null) {
            return $current->format('Y-m-d') === $scanned;
        }

        return $this->normalize($current) === $this->normalize($scanned);
    }
}
