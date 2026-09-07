<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentMetadataQueueService;
use Illuminate\Console\Command;

class QueueDocumentMetadataExtraction extends Command
{
    protected $signature = 'documents:queue-metadata-extraction
        {--direction= : incoming hoặc outgoing}
        {--branch= : Chỉ xử lý ID chi nhánh này}
        {--year= : Chỉ xử lý năm của ngày vào sổ}
        {--limit= : Giới hạn số văn bản trong một lần chạy}
        {--all : Bao gồm cả văn bản đăng thủ công; mặc định chỉ kho cũ}
        {--dry-run : Chỉ đếm, không đưa vào hàng đợi}';

    protected $description = 'Đưa PDF còn thiếu ngày văn bản hoặc trích yếu vào hàng đợi trích xuất/OCR';

    public function handle(DocumentMetadataQueueService $queueService): int
    {
        $direction = $this->option('direction');
        if ($direction !== null && ! in_array($direction, [Document::DIRECTION_INCOMING, Document::DIRECTION_OUTGOING], true)) {
            $this->error('--direction chỉ nhận incoming hoặc outgoing.');

            return self::FAILURE;
        }

        $capabilities = $queueService->capabilities();
        $this->line($capabilities['message']);
        if (! $capabilities['available'] && ! $this->option('dry-run')) {
            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $ids = $queueService->candidateIds(
            $direction,
            $this->option('branch') !== null ? (int) $this->option('branch') : null,
            $this->option('year') !== null ? (int) $this->option('year') : null,
            (bool) $this->option('all'),
            $limit,
        );

        if ($this->option('dry-run')) {
            $this->info('Có '.$ids->count().' văn bản phù hợp để đưa vào hàng đợi.');

            return self::SUCCESS;
        }

        $queued = $queueService->dispatchIds($ids);

        $this->info('Đã đưa '.$queued.' văn bản vào queue "'.config('documents.metadata_extraction.queue', 'document-ocr').'".');

        return self::SUCCESS;
    }
}
