<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DocumentLedgerImportService;
use App\Services\DocumentLedgerReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ImportDocumentLedger extends Command
{
    protected $signature = 'documents:import-ledger
        {ledger : Đường dẫn file Excel sổ văn thư}
        {--user= : ID văn thư của chi nhánh sở hữu sổ}
        {--direction=incoming : incoming hoặc outgoing}
        {--year= : Năm sổ cần nhập, dựa theo ngày đến/ngày chuyển}
        {--sheet=* : Tên sheet cần nhập; có thể truyền nhiều lần}
        {--overwrite : Cập nhật các trường đã có bằng ô có nội dung trong Excel}
        {--update-only : Chỉ cập nhật và vào sổ văn bản đã có; không tạo văn bản mới}
        {--dry-run : Chỉ đối chiếu; không ghi dữ liệu hoặc cấp số}
        {--yes : Xác nhận nhập thật}';

    protected $description = 'Nhập/cập nhật sổ đến và đi theo năm, kể cả văn bản không đính kèm file';

    public function handle(DocumentLedgerReader $reader, DocumentLedgerImportService $importer): int
    {
        $user = User::with('branch')->find((int) $this->option('user'));
        $year = (int) $this->option('year');
        $direction = (string) $this->option('direction');
        if (! $user?->isClerk() || ! $user->branch_id) {
            $this->error('--user phải là ID Văn thư có chi nhánh.');
            return self::FAILURE;
        }
        if ($year < 2000 || $year > 2100 || ! in_array($direction, ['incoming', 'outgoing'], true) || $this->option('sheet') === []) {
            $this->error('Cần --year=2000..2100, --direction=incoming|outgoing và ít nhất một --sheet="Tên sheet".');
            return self::FAILURE;
        }
        if (! Schema::hasTable('document_ledger_entries')) {
            $this->error('Chưa có bảng sổ. Chạy php artisan migrate --path=database/migrations/main trước.');
            return self::FAILURE;
        }
        $dryRun = (bool) $this->option('dry-run');
        if (! $dryRun && ! $this->option('yes') && ! $this->confirm('Nhập/cập nhật sổ năm '.$year.' cho '.$user->branch->branch_name.'?')) {
            return self::SUCCESS;
        }
        try {
            $path = (string) $this->argument('ledger');
            $rows = $reader->read($path, $direction, $this->option('sheet'));
            $stats = $importer->import($rows, $user, $year, basename($path), $dryRun, (bool) $this->option('overwrite'), (bool) $this->option('update-only'));
            $this->table(['Dòng đọc', 'Tạo mới', 'Cập nhật', 'Không đổi', 'Bỏ qua', 'Cần kiểm tra'], [[
                $stats['rows'], $stats['created'], $stats['updated'], $stats['unchanged'], $stats['skipped'], $stats['conflicts'],
            ]]);
            $yearRows = [];
            foreach ($stats['rows_by_year'] as $sourceYear => $count) {
                $yearRows[] = [$sourceYear === 'unknown' ? 'Chưa xác định' : $sourceYear, $count];
            }
            $this->table(['Năm theo ngày đến/ngày chuyển', 'Dòng trong Excel'], $yearRows);
            $this->line('Bỏ qua do khác năm: '.$stats['skipped_other_year'].'. Thiếu số, ký hiệu trong phạm vi nhập: '.$stats['skipped_missing_code'].'.');
            if ($stats['issues']) {
                $this->warn('Dòng chưa nhập: '.$stats['issue_count'].' (hiển thị tối đa 100). Dòng thuộc năm khác được bỏ qua riêng.');
                $this->table(['Sheet', 'Dòng', 'Lý do'], $stats['issues']);
            }
            $this->info($dryRun ? 'Chạy thử hoàn tất; chưa ghi dữ liệu.' : 'Đã xử lý các dòng hợp lệ. Không phát thông báo văn bản mới.');
            return $stats['issue_count'] > 0 ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
