<?php

namespace App\Console\Commands;

use App\Exports\OcrResultsExport;
use App\Models\Document;
use App\Services\DocumentReviewDirectionClassifier;
use App\Services\PdfDocumentReviewScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportOcrToExcelCommand extends Command
{
    protected $signature = 'ocr:export-excel
        {--limit=50 : Số văn bản tối đa; 0 để quét tất cả văn bản thiếu dữ liệu hoặc chưa phân loại}
        {--id=* : Chỉ xét các ID này, vẫn bỏ qua văn bản đã đủ dữ liệu và đã phân loại}
        {--branch= : Giới hạn chi nhánh sở hữu}
        {--after-id=0 : Chỉ xét ID lớn hơn giá trị này}
        {--file= : Đường dẫn .xlsx tương đối trong storage/app/private}';

    protected $description = 'Đọc PDF của văn bản thiếu ngày/trích yếu hoặc chưa phân loại và xuất Excel kiểm tra, không cập nhật database';

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
        $file = str_replace('\\', '/', (string) ($this->option('file') ?: 'ocr-review/kiem-tra-pdf-'.now()->format('Ymd-His').'.xlsx'));
        if (! preg_match('/\.xlsx$/i', $file) || preg_match('~(^/|^[a-z]:|(^|/)\.\.(/|$)|[\x00-\x1f])~i', $file)) {
            $this->error('--file phải là đường dẫn .xlsx tương đối trong storage/app/private.');

            return self::INVALID;
        }
        $disk = Storage::disk('local');
        if ($disk->exists($file) || $disk->exists($file.'.jsonl')) {
            $this->error('File kết quả đã tồn tại. Chọn tên mới để giữ lại đợt kiểm tra trước.');

            return self::FAILURE;
        }
        $query = Document::query()->with(['attachments' => fn ($q) => $q->orderBy('id')])
            ->where(fn ($q) => $q->whereNull('issued_date')
                ->orWhereNull('title')
                ->orWhereRaw("TRIM(REPLACE(REPLACE(REPLACE(title, CHAR(9), ''), CHAR(10), ''), CHAR(13), '')) = ''")
                ->orWhereRaw("LOWER(TRIM(COALESCE(direction, ''))) IN (?, ?)", ['', Document::DIRECTION_UNCLASSIFIED]))
            ->where('id', '>', (int) $this->option('after-id'))
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))
            ->when($this->option('branch') !== null, fn ($q) => $q->where('managing_branch_id', (int) $this->option('branch')))
            ->orderBy('id');
        $count = (clone $query)->count();
        $limit = (int) $this->option('limit');
        $count = $limit > 0 ? min($limit, $count) : $count;
        if ($count === 0) {
            $this->info('Không có văn bản thiếu ngày/trích yếu hoặc chưa phân loại trong phạm vi đã chọn.');

            return self::SUCCESS;
        }
        $this->info("Quét {$count} văn bản thiếu ngày/trích yếu hoặc chưa phân loại. Chỉ đọc và xuất Excel; không cập nhật database.");
        $rows = [];
        $processed = 0;
        foreach ($query->lazyById(100) as $document) {
            if ($processed >= $count) {
                break;
            }
            $processed++;
            $missing = [];
            if (blank($document->issued_date)) {
                $missing[] = 'Ngày văn bản';
            }
            if (blank($document->title)) {
                $missing[] = 'Trích yếu';
            }
            $needsClassification = blank($document->direction) || $document->direction === Document::DIRECTION_UNCLASSIFIED;
            if ($needsClassification) {
                $missing[] = 'Phân loại';
            }
            $base = ['id' => $document->id, 'missing' => implode(', ', $missing),
                'current_code' => $document->document_code, 'current_date' => $document->issued_date?->format('Y-m-d'),
                'current_title' => $document->title, 'current_agency' => $document->issuing_agency,
                'current_direction' => $document->direction];
            $attachments = $document->attachments->filter(fn ($a) => strtolower((string) $a->file_extension) === 'pdf' || str_ends_with(strtolower((string) $a->file_name), '.pdf'));
            if ($attachments->isEmpty()) {
                $row = $base + ['status' => 'Không có PDF', 'warning' => 'Không có tệp PDF đính kèm.',
                    'direction' => null, 'direction_reason' => 'Không có PDF để phân loại.'];
                $rows[] = $row;
                $this->checkpoint($file, $row);
            }
            foreach ($attachments as $attachment) {
                $row = $base + ['attachment_id' => $attachment->id, 'file' => $attachment->file_name,
                    'url' => $attachment->view_url, 'path' => $attachment->file_path];
                try {
                    if (! Storage::disk('public')->exists($attachment->file_path)) {
                        throw new \RuntimeException('Không tìm thấy file PDF trên ổ lưu trữ.');
                    }
                    $result = $scanner->scan(Storage::disk('public')->path($attachment->file_path));
                    $metadata = $result['metadata'];
                    $classification = $classifier->classify($result['text'], $metadata);
                    $neededFound = (filled($document->issued_date) || filled($metadata['issued_date']))
                        && (filled($document->title) || filled($metadata['title']))
                        && (! $needsClassification || filled($classification['direction']));
                    $warnings = $metadata['warnings'];
                    if (preg_match('/\d+/', (string) $metadata['document_code'], $readNumber)
                        && preg_match('/\d+/', (string) $document->document_code, $storedNumber)
                        && ltrim($readNumber[0], '0') !== ltrim($storedNumber[0], '0')) {
                        $warnings[] = 'Số đọc được khác số/ký hiệu đang lưu; cần đối chiếu PDF.';
                    }
                    if ($attachments->count() > 1) {
                        $warnings[] = 'Văn bản có nhiều PDF; đối chiếu từng tệp.';
                    }
                    if (filled($document->issued_date) && filled($metadata['issued_date']) && $base['current_date'] !== $metadata['issued_date']) {
                        $warnings[] = 'Ngày đọc từ PDF khác ngày đang lưu; chỉ hiển thị để đối chiếu.';
                    }
                    $row += ['code' => $metadata['document_code'], 'date' => $metadata['issued_date'],
                        'agency' => $metadata['issuing_agency'], 'title' => $metadata['title'],
                        'status' => $neededFound ? 'Có đề xuất; cần đối chiếu' : 'Chưa đọc đủ trường còn thiếu',
                        'warning' => implode(' ', $warnings), 'page' => $result['page'], 'region' => $result['region'],
                        'text' => $result['text'], 'sha256' => $result['sha256'],
                        'direction' => $classification['direction'], 'direction_reason' => $classification['reason']];
                } catch (Throwable $e) {
                    $row += ['status' => 'Lỗi đọc PDF', 'warning' => mb_substr($e->getMessage(), 0, 1500),
                        'direction' => null, 'direction_reason' => 'Không đọc được chữ để phân loại.'];
                }
                $rows[] = $row;
                $this->checkpoint($file, $row);
            }
            $this->line("[{$processed}/{$count}] #{$document->id}: ".end($rows)['status']);
        }
        if (! Excel::store(new OcrResultsExport($rows), $file, 'local')) {
            $this->error('Không ghi được Excel. Kết quả từng dòng vẫn nằm trong '.$disk->path($file.'.jsonl'));

            return self::FAILURE;
        }
        $this->info('Đã xuất: '.$disk->path($file));
        $this->table(['Kết quả', 'Số dòng PDF'], collect($rows)->countBy('status')->map(fn ($n, $s) => [$s, $n])->values()->all());
        $this->info('Database chưa được cập nhật. Xem và ghi nhận xét ở cột Kiểm tra của bạn.');

        return self::SUCCESS;
    }

    private function checkpoint(string $file, array $row): void
    {
        if (! Storage::disk('local')->append($file.'.jsonl', json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))) {
            throw new \RuntimeException('Không ghi được kết quả kiểm tra tạm.');
        }
    }
}
