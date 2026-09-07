<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentLedgerEnrichmentService;
use App\Services\DocumentLedgerReader;
use App\Services\DocumentMetadataQueueService;
use Illuminate\Console\Command;
use Throwable;

class EnrichDocumentMetadataFromLedger extends Command
{
    protected $signature = 'documents:enrich-from-ledger
        {ledger : Đường dẫn file Excel sổ văn bản}
        {--direction=incoming : incoming hoặc outgoing}
        {--user= : ID văn thư, đồng thời xác định chi nhánh quản lý}
        {--overwrite : Ghi đè cả trường đã có dữ liệu}
        {--reclassify : Cho phép sửa loại đến/đi theo cuốn sổ đang đối chiếu}
        {--queue-ocr-missing : Sau khi đối chiếu sổ, xếp hàng OCR các PDF vẫn thiếu ngày hoặc trích yếu}
        {--dry-run : Chỉ đối chiếu và báo kết quả}
        {--yes : Xác nhận cập nhật thật không cần hỏi lại}';

    protected $description = 'Bổ sung metadata văn bản từ sổ Excel theo số, ký hiệu; không tạo thông báo văn bản mới';

    public function handle(
        DocumentLedgerReader $reader,
        DocumentLedgerEnrichmentService $enricher,
        DocumentMetadataQueueService $queueService,
    ): int {
        $ledger = realpath((string) $this->argument('ledger'));
        if ($ledger === false || ! is_file($ledger) || ! is_readable($ledger)) {
            $this->error('Không đọc được file sổ Excel. Kiểm tra lại đường dẫn hoặc ổ dùng chung.');

            return self::FAILURE;
        }

        $direction = (string) $this->option('direction');
        if (! in_array($direction, [Document::DIRECTION_INCOMING, Document::DIRECTION_OUTGOING], true)) {
            $this->error('--direction chỉ nhận incoming hoặc outgoing.');

            return self::FAILURE;
        }

        $user = User::with('branch')->find((int) $this->option('user'));
        if (! $user || ! $user->canUploadDocument()) {
            $this->error('Phải truyền --user là ID văn thư thuộc Chi nhánh loại I.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        if (! $dryRun && ! $this->option('yes') && ! $this->confirm('Cập nhật metadata các văn bản khớp với sổ Excel?')) {
            $this->warn('Đã hủy, chưa có dữ liệu nào được thay đổi.');

            return self::SUCCESS;
        }

        try {
            $rows = $reader->read($ledger, $direction);
            $stats = $enricher->enrich(
                $rows,
                $direction,
                (int) $user->branch_id,
                (int) $user->id,
                basename($ledger),
                $dryRun,
                (bool) $this->option('overwrite'),
                (bool) $this->option('reclassify'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Dòng sổ', 'Văn bản đối chiếu', 'Khớp', $dryRun ? 'Có thể cập nhật' : 'Đã cập nhật', 'Không đổi', 'Không thấy', 'Mơ hồ', 'Số trường'],
            [[
                $stats['ledger_rows'],
                $stats['documents'],
                $stats['matched'],
                $stats['updated'],
                $stats['unchanged'],
                $stats['unmatched'],
                $stats['ambiguous'],
                $stats['fields'],
            ]]
        );

        if ($stats['unresolved'] !== []) {
            $this->newLine();
            $this->warn('Các văn bản chưa ghép được (tối đa 20):');
            $this->table(['Trạng thái', 'Số, ký hiệu'], array_map(
                fn (array $row) => [$row['status'], $row['document_code']],
                $stats['unresolved']
            ));
        }

        if (! $dryRun && $this->option('queue-ocr-missing')) {
            $capabilities = $queueService->capabilities();
            $this->line($capabilities['message']);
            if ($capabilities['available']) {
                $ids = $queueService->candidateIds($direction, (int) $user->branch_id);
                $queued = $queueService->dispatchIds($ids);
                $this->info('Đã xếp hàng trích xuất/OCR '.$queued.' PDF còn thiếu metadata.');
            } else {
                $this->warn('Dữ liệu từ Excel vẫn đã được xử lý; chưa xếp OCR vì máy chủ thiếu engine.');
            }
        }

        return self::SUCCESS;
    }
}
