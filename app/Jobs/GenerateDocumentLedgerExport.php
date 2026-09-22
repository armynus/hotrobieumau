<?php

namespace App\Jobs;

use App\Models\DocumentExportOperation;
use App\Services\DocumentLedgerExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class GenerateDocumentLedgerExport implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $documentExportId)
    {
        $this->timeout = max(60, (int) config('documents.exports.job_timeout', 240));
        $this->onQueue((string) config('documents.exports.queue', 'document-exports'));
    }

    public function uniqueId(): string
    {
        return (string) $this->documentExportId;
    }

    public function backoff(): array
    {
        return [60];
    }

    public function handle(DocumentLedgerExportService $service): void
    {
        $operation = DocumentExportOperation::find($this->documentExportId);
        if (! $operation || $operation->status === 'completed') {
            return;
        }

        $operation->update([
            'status' => 'running',
            'error_message' => null,
            'started_at' => now(),
            'finished_at' => null,
        ]);

        if (! Excel::store(
            $service->workbook($operation),
            $operation->path,
            $operation->disk,
            ExcelWriter::XLSX,
        )) {
            throw new RuntimeException('Không ghi được file sổ văn bản vào bộ nhớ máy chủ.');
        }

        $operation->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
        $operation = DocumentExportOperation::find($this->documentExportId);
        if (! $operation) {
            return;
        }

        Storage::disk($operation->disk)->delete($operation->path);
        $operation->update([
            'status' => 'failed',
            'error_message' => 'Không thể tạo file Excel. Hãy thử lại hoặc chọn khoảng thời gian ngắn hơn.',
            'finished_at' => now(),
        ]);
    }
}
