<?php

namespace App\Services;

use App\Jobs\ExtractDocumentMetadata;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DocumentMetadataQueueService
{
    public function __construct(private readonly PdfDocumentTextExtractor $extractor) {}

    /**
     * @return array{available:bool, embedded_text:bool, ocr:bool, message:string}
     */
    public function capabilities(): array
    {
        return $this->extractor->capabilities();
    }

    public function candidateQuery(
        ?string $direction = null,
        ?int $branchId = null,
        ?int $year = null,
        bool $includeManual = false,
    ): Builder {
        $query = Document::query()
            ->where(fn ($query) => $query->whereNull('issued_date')->orWhereNull('title')->orWhere('title', ''))
            ->whereHas('attachments', fn ($query) => $query
                ->where('file_extension', 'pdf')
                ->orWhere('file_name', 'like', '%.pdf'));

        if (! $includeManual) {
            $query->whereHas('logs', fn ($query) => $query->where('action', 'archive_imported'));
        }
        if ($direction !== null) {
            $query->where('direction', $direction);
        }
        if ($branchId !== null) {
            $query->where('managing_branch_id', $branchId);
        }
        if ($year !== null) {
            $query->where(function ($query) use ($year): void {
                $query->whereYear('received_date', $year)
                    ->orWhereYear('forwarded_date', $year)
                    ->orWhereYear('issued_date', $year);
            });
        }

        return $query;
    }

    /**
     * @return Collection<int, int>
     */
    public function candidateIds(
        ?string $direction = null,
        ?int $branchId = null,
        ?int $year = null,
        bool $includeManual = false,
        ?int $limit = null,
    ): Collection {
        return $this->candidateQuery($direction, $branchId, $year, $includeManual)
            ->orderBy('id')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * @param  iterable<int>  $documentIds
     */
    public function dispatchIds(iterable $documentIds): int
    {
        $documentIds = collect($documentIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($documentIds->isEmpty()) {
            return 0;
        }

        $eligibleIds = $this->candidateQuery(includeManual: true)
            ->whereIn('id', $documentIds)
            ->orderBy('id')
            ->pluck('id');
        $count = 0;
        foreach ($eligibleIds as $documentId) {
            ExtractDocumentMetadata::dispatch((int) $documentId);
            $count++;
        }

        return $count;
    }
}
