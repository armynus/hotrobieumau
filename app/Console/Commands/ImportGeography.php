<?php

namespace App\Console\Commands;

use App\Services\Geography\GeographyImportService;
use App\Services\Geography\GeographyStore;
use Illuminate\Console\Command;

class ImportGeography extends Command
{
    protected $signature = 'geography:import {file? : Excel/CSV hoặc JSON chuẩn hóa} {--prepared : Dùng bộ toàn quốc từ 3 file Excel} {--yes : Xác nhận thay bộ tra cứu đang dùng}';

    protected $description = 'Nhập dữ liệu xã/phường dùng ngay, kèm lọc trùng và ghi chú đối chiếu';

    public function handle(GeographyImportService $importer, GeographyStore $store): int
    {
        if (! $store->ready()) {
            $this->error('Chạy migration 2026_09_25_140000_create_geography_catalog_tables.php trước.');

            return self::FAILURE;
        }
        $file = $this->option('prepared') ? config('geography.prepared.national.path') : $this->argument('file');
        if (! $file || ! is_file($file)) {
            $this->error('Chọn file hợp lệ hoặc --prepared.');

            return self::FAILURE;
        }
        if ($store->current() && ! $this->option('yes') && ! $this->confirm('Thay bộ tra cứu đang dùng? Bản nguồn cũ vẫn được giữ.')) {
            return self::FAILURE;
        }
        $id = $importer->importFile($file, $this->option('prepared') ? 'Toàn quốc — tổng hợp 3 file Excel' : basename($file));
        $rows = $store->rows($id);
        $this->info('Đã nhập: '.(clone $rows)->where('enabled', true)->count().' liên kết, '.(clone $rows)->where('enabled', false)->count().' dòng đã loại.');
        $this->line('Dữ liệu đã dùng ngay trong tra cứu. Không thay địa chỉ khách hàng hay các bảng tra cứu cũ.');

        return self::SUCCESS;
    }
}
