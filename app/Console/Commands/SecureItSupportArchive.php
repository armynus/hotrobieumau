<?php

namespace App\Console\Commands;

use App\Models\ItSupportRequest;
use App\Services\ItSupportStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SecureItSupportArchive extends Command
{
    protected $signature = 'it-support:secure-archive {--execute : Move legacy public files into private storage and write completed ticket archives}';

    protected $description = 'Secure legacy IT attachments and organize completed tickets by completion date';

    public function handle(ItSupportStorage $storage): int
    {
        $total = ItSupportRequest::count();
        if (! $this->option('execute')) {
            $this->info("Tìm thấy {$total} phiếu. Chạy lại với --execute để chuyển tệp và tạo hồ sơ lưu trữ.");
            return self::SUCCESS;
        }
        $done = 0;
        $failed = 0;
        ItSupportRequest::orderBy('id')->chunkById(100, function ($tickets) use ($storage, &$done, &$failed) {
            foreach ($tickets as $ticket) {
                try {
                    $obsolete = DB::transaction(function () use ($ticket, $storage) {
                        if (! $ticket->completed_at && in_array($ticket->status, ['resolved', 'closed'], true)) {
                            $ticket->completed_at = $ticket->updated_at ?: now();
                            $ticket->save();
                        }
                        return $storage->secureAndArchive($ticket);
                    });
                    foreach ($obsolete as $file) {
                        Storage::disk($file['disk'])->delete($file['path']);
                    }
                    $done++;
                } catch (Throwable $error) {
                    $this->error("Phiếu #{$ticket->id}: {$error->getMessage()}");
                    $failed++;
                }
            }
        });
        $this->info("Đã xử lý {$done}/{$total} phiếu; lỗi: {$failed}.");
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
