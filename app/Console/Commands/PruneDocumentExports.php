<?php

namespace App\Console\Commands;

use App\Models\DocumentExportOperation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneDocumentExports extends Command
{
    protected $signature = 'documents:prune-exports';

    protected $description = 'Xóa file và trạng thái xuất sổ văn bản đã hết hạn';

    public function handle(): int
    {
        $deleted = 0;
        DocumentExportOperation::query()
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($operations) use (&$deleted): void {
                foreach ($operations as $operation) {
                    Storage::disk($operation->disk)->delete($operation->path);
                    $operation->delete();
                    $deleted++;
                }
            });

        $this->info("Đã xóa {$deleted} file xuất sổ hết hạn.");

        return self::SUCCESS;
    }
}
