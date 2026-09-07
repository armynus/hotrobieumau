<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Support\DocumentStoragePath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class ReorganizeDocumentStorage extends Command
{
    protected $signature = 'documents:reorganize-storage
        {--dry-run : Chỉ xem đường dẫn cũ và mới, không đổi file hay database}
        {--yes : Thực hiện ngay không hỏi xác nhận}';

    protected $description = 'Chuyển file văn bản sang cấu trúc NAM/THANG/NGAY và cập nhật đường dẫn đính kèm';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $dryRun = (bool) $this->option('dry-run');
        $attachmentCount = DB::table('document_attachments')->count();

        if ($attachmentCount === 0) {
            $this->info('Không có file đính kèm cần chuyển.');
            return self::SUCCESS;
        }

        if (!$dryRun && !$this->option('yes') && !$this->confirm("Chuyển {$attachmentCount} đường dẫn file sang cấu trúc NAM/THANG/NGAY?")) {
            $this->warn('Đã hủy, chưa có file hay dữ liệu nào được thay đổi.');
            return self::SUCCESS;
        }

        $stats = [
            'examined' => 0,
            'moved' => 0,
            'already_correct' => 0,
            'recovered' => 0,
            'missing' => 0,
            'failed' => 0,
        ];
        $preview = [];

        DB::table('document_attachments')
            ->leftJoin('documents', 'documents.id', '=', 'document_attachments.document_id')
            ->select([
                'document_attachments.id as attachment_id',
                'document_attachments.file_path',
                'documents.direction',
                'documents.issued_date',
                'documents.received_date',
                'documents.forwarded_date',
                'documents.created_at as document_created_at',
            ])
            ->orderBy('document_attachments.id')
            ->chunkById(200, function ($attachments) use ($disk, $dryRun, &$stats, &$preview): void {
                foreach ($attachments as $attachment) {
                    $stats['examined']++;
                    $oldPath = str_replace('\\', '/', (string) $attachment->file_path);

                    if (DocumentStoragePath::usesNamedStructure($oldPath)) {
                        $stats['already_correct']++;
                        continue;
                    }

                    $date = DocumentStoragePath::dateFromLegacyPath($oldPath)
                        ?? $this->documentDate($attachment);

                    if (!$date) {
                        $stats['failed']++;
                        $this->error('Không xác định được ngày lưu cho: ' . $oldPath);
                        continue;
                    }

                    $targetDirectory = DocumentStoragePath::directoryForDate($date);
                    $targetPath = $targetDirectory . '/' . basename($oldPath);

                    if ($disk->exists($targetPath) && $disk->exists($oldPath)) {
                        $targetPath = $this->uniqueTargetPath($targetDirectory, basename($oldPath));
                    }

                    if (count($preview) < 20) {
                        $preview[] = [$oldPath, $targetPath];
                    }

                    if ($dryRun) {
                        continue;
                    }

                    try {
                        if (!$disk->exists($oldPath)) {
                            $targetIsUnclaimed = $disk->exists($targetPath)
                                && !DB::table('document_attachments')
                                    ->where('id', '<>', $attachment->attachment_id)
                                    ->where('file_path', $targetPath)
                                    ->exists();

                            if ($targetIsUnclaimed) {
                                DB::table('document_attachments')
                                    ->where('id', $attachment->attachment_id)
                                    ->update(['file_path' => $targetPath]);
                                $stats['recovered']++;
                                continue;
                            }

                            $stats['missing']++;
                            $this->warn('Không tìm thấy file: ' . $oldPath);
                            continue;
                        }

                        $disk->makeDirectory($targetDirectory);
                        if (!$disk->move($oldPath, $targetPath)) {
                            throw new \RuntimeException('Không thể di chuyển file.');
                        }

                        try {
                            DB::table('document_attachments')
                                ->where('id', $attachment->attachment_id)
                                ->update(['file_path' => $targetPath]);
                        } catch (Throwable $exception) {
                            $disk->move($targetPath, $oldPath);
                            throw $exception;
                        }

                        $stats['moved']++;
                    } catch (Throwable $exception) {
                        $stats['failed']++;
                        $this->error("Lỗi {$oldPath}: {$exception->getMessage()}");
                    }
                }
            }, 'document_attachments.id', 'attachment_id');

        if ($preview !== []) {
            $this->table(['Đường dẫn cũ (tối đa 20)', 'Đường dẫn mới'], $preview);
        }

        $removedDirectories = $dryRun ? 0 : $this->removeEmptyLegacyDirectories();

        $this->table(
            ['Đã kiểm tra', 'Đã chuyển', 'Đúng sẵn', 'Khôi phục dở dang', 'Thiếu file', 'Lỗi', 'Folder cũ đã dọn'],
            [[
                $stats['examined'],
                $stats['moved'],
                $stats['already_correct'],
                $stats['recovered'],
                $stats['missing'],
                $stats['failed'],
                $removedDirectories,
            ]],
        );

        return ($stats['missing'] + $stats['failed']) > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function documentDate(object $attachment): ?string
    {
        if ($attachment->direction === Document::DIRECTION_OUTGOING) {
            return $attachment->forwarded_date
                ?: $attachment->issued_date
                ?: $attachment->document_created_at;
        }

        return $attachment->received_date
            ?: $attachment->issued_date
            ?: $attachment->document_created_at;
    }

    private function uniqueTargetPath(string $directory, string $fileName): string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $counter = 1;

        do {
            $candidate = $directory . '/' . $baseName . ' (' . $counter . ')'
                . ($extension !== '' ? '.' . $extension : '');
            $counter++;
        } while (Storage::disk('public')->exists($candidate));

        return $candidate;
    }

    private function removeEmptyLegacyDirectories(): int
    {
        $documentsRoot = Storage::disk('public')->path('documents');
        $resolvedRoot = realpath($documentsRoot);
        if ($resolvedRoot === false || !is_dir($resolvedRoot)) {
            return 0;
        }

        $removed = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolvedRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if (!$item->isDir()) {
                continue;
            }

            $resolvedDirectory = $item->getRealPath();
            if ($resolvedDirectory === false || !str_starts_with($resolvedDirectory, $resolvedRoot . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($resolvedDirectory, strlen($resolvedRoot) + 1));
            if ($relative !== '.deleting' && !preg_match('#^\d{4}(?:/\d{2}(?:/\d{2})?)?$#', $relative)) {
                continue;
            }

            // rmdir chỉ xóa folder rỗng, không xóa đệ quy hay đụng file còn sót.
            if (@rmdir($resolvedDirectory)) {
                $removed++;
            }
        }

        return $removed;
    }
}
