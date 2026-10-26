<?php

namespace App\Console\Commands;

use App\Services\ActivityLogService;
use Illuminate\Console\Command;

class PruneExpiredActivityLogs extends Command
{
    /**
     * Tên và chữ ký của lệnh console
     *
     * @var string
     */
    protected $signature = 'logs:prune-expired {--years=5 : Số năm lưu trữ tối đa}';

    /**
     * Mô tả của lệnh console
     *
     * @var string
     */
    protected $description = 'Tự động dọn dẹp các bản ghi nhật ký hoạt động đã quá 5 năm (không ảnh hưởng session đang hoạt động)';

    /**
     * Thực thi lệnh console
     */
    public function handle(): int
    {
        $years = (int) $this->option('years');
        if ($years < 1) {
            $years = 5;
        }

        $this->info("Bắt đầu dọn dẹp nhật ký hoạt động và phiên lịch sử cũ hơn {$years} năm...");

        $result = ActivityLogService::pruneExpiredLogs($years);

        $this->info("Hoàn tất: Đã xóa {$result['logs']} bản ghi nhật ký hoạt động và {$result['sessions']} bản ghi phiên lịch sử đã kết thúc.");
        $this->line("Các phiên đang hoạt động (active) được bảo vệ và giữ nguyên 100%.");

        return self::SUCCESS;
    }
}
