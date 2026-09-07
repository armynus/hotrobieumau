<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentLog;
use App\Services\DocumentMetadataParser;
use App\Services\PdfDocumentTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExtractDocumentMetadata implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $documentId)
    {
        $this->onQueue((string) config('documents.metadata_extraction.queue', 'document-ocr'));
    }

    public function uniqueId(): string
    {
        return (string) $this->documentId;
    }

    public function backoff(): array
    {
        return [60];
    }

    public function handle(PdfDocumentTextExtractor $extractor, DocumentMetadataParser $parser): void
    {
        $document = Document::with('attachments')->find($this->documentId);
        if (! $document || (filled($document->issued_date) && filled($document->title))) {
            return;
        }

        $attachment = $document->attachments->first(function ($attachment) {
            return mb_strtolower((string) $attachment->file_extension) === 'pdf'
                || str_ends_with(mb_strtolower((string) $attachment->file_name), '.pdf');
        });

        if (! $attachment || ! Storage::disk('public')->exists($attachment->file_path)) {
            throw new \RuntimeException('Không tìm thấy file PDF đính kèm của văn bản #'.$document->id);
        }

        $result = $extractor->extract(Storage::disk('public')->path($attachment->file_path));
        $metadata = $parser->parse($result['text']);
        $updates = [];
        if (blank($document->issued_date) && filled($metadata['issued_date'])) {
            $updates['issued_date'] = $metadata['issued_date'];
        }
        if (blank($document->title) && filled($metadata['title'])) {
            $updates['title'] = $metadata['title'];
        }

        DB::transaction(function () use ($document, $updates, $result): void {
            if ($updates !== []) {
                $document->update($updates);
            }

            DocumentLog::create([
                'document_id' => $document->id,
                'user_id' => null,
                'action' => $updates === [] ? 'metadata_extraction_no_result' : 'metadata_extracted',
                'details' => [
                    'engine' => $result['engine'],
                    'updated_fields' => array_keys($updates),
                ],
            ]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        $document = Document::find($this->documentId);
        if (! $document) {
            return;
        }

        DocumentLog::create([
            'document_id' => $document->id,
            'user_id' => null,
            'action' => 'metadata_extraction_failed',
            'details' => ['error' => mb_strcut((string) $exception?->getMessage(), 0, 1000, 'UTF-8')],
        ]);
    }
}
