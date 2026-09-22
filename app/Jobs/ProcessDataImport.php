<?php

namespace App\Jobs;

use App\Imports\AccountInfoImport;
use App\Imports\CustomerInfoImport;
use App\Imports\TenantBatchImport;
use App\Models\DataImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class ProcessDataImport implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 240;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $dataImportId)
    {
        $this->onQueue('data-imports');
    }

    public function uniqueId(): string
    {
        return (string) $this->dataImportId;
    }

    public function backoff(): array
    {
        return [60];
    }

    public function handle(): void
    {
        $operation = DataImport::find($this->dataImportId);
        if (! $operation || $operation->status === 'completed') {
            return;
        }

        $disk = Storage::disk($operation->disk);
        if (! $disk->exists($operation->path)) {
            throw new RuntimeException('File nhập không còn trên máy chủ.');
        }

        $absolutePath = $disk->path($operation->path);
        $operation->update([
            'status' => 'running',
            'total_rows' => $this->totalRows($absolutePath),
            'processed_rows' => 0,
            'inserted_rows' => 0,
            'updated_rows' => 0,
            'skipped_rows' => 0,
            'error_message' => null,
            'started_at' => now(),
            'finished_at' => null,
        ]);

        config(['database.connections.tenant.database' => $operation->tenant_database]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        try {
            $progress = function (TenantBatchImport $import): void {
                DataImport::whereKey($this->dataImportId)->update([
                    'processed_rows' => $import->processed,
                    'inserted_rows' => $import->inserted,
                    'updated_rows' => $import->updated,
                    'skipped_rows' => $import->skipped,
                ]);
            };
            $import = $operation->type === 'customer'
                ? new CustomerInfoImport($progress)
                : new AccountInfoImport($progress);

            Excel::import($import, $absolutePath);

            $operation->update([
                'status' => 'completed',
                'processed_rows' => $import->processed,
                'inserted_rows' => $import->inserted,
                'updated_rows' => $import->updated,
                'skipped_rows' => $import->skipped,
                'finished_at' => now(),
            ]);
            $disk->delete($operation->path);
        } finally {
            DB::disconnect('tenant');
        }
    }

    public function failed(?Throwable $exception): void
    {
        $operation = DataImport::find($this->dataImportId);
        if (! $operation) {
            return;
        }

        $operation->update([
            'status' => 'failed',
            'error_message' => mb_strcut((string) $exception?->getMessage(), 0, 2000, 'UTF-8'),
            'finished_at' => now(),
        ]);
        Storage::disk($operation->disk)->delete($operation->path);
    }

    private function totalRows(string $path): int
    {
        $worksheets = IOFactory::createReaderForFile($path)->listWorksheetInfo($path);

        return max(0, (int) ($worksheets[0]['totalRows'] ?? 1) - 1);
    }
}
