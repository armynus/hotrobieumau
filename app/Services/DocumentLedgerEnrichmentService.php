<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLog;
use App\Support\DocumentStoragePath;
use Illuminate\Support\Facades\DB;

class DocumentLedgerEnrichmentService
{
    private const METADATA_FIELDS = [
        'registry_number',
        'issued_date',
        'received_date',
        'forwarded_date',
        'issuing_agency',
        'signer',
        'title',
        'recipient',
        'archive_recipient',
        'copy_count',
        'receipt_signature',
        'notes',
    ];

    public function __construct(private readonly DocumentLedgerMatcher $matcher) {}

    /**
     * @param  array<int, array<string, mixed>>  $ledgerRows
     * @return array<string, mixed>
     */
    public function enrich(
        array $ledgerRows,
        string $direction,
        int $branchId,
        ?int $userId,
        string $sourceName,
        bool $dryRun = false,
        bool $overwrite = false,
        bool $reclassify = false,
    ): array {
        $documents = Document::query()
            ->when(! $reclassify, fn ($query) => $query->whereIn('direction', $direction === Document::DIRECTION_OUTGOING
                ? [Document::DIRECTION_OUTGOING, Document::DIRECTION_DECISION] : [$direction]))
            ->where('managing_branch_id', $branchId)
            ->with([
                'attachments:id,document_id,file_path,archive_relative_path',
                'logs' => fn ($query) => $query->select('id', 'document_id', 'action')->where('action', 'archive_imported'),
            ])
            ->get();

        $ledgerIndex = $this->matcher->index($ledgerRows);

        $stats = [
            'ledger_rows' => count($ledgerRows),
            'documents' => $documents->count(),
            'matched' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'unmatched' => 0,
            'ambiguous' => 0,
            'fields' => 0,
            'unresolved' => [],
        ];
        // Đối chiếu theo từng văn bản, rồi để matcher chọn dòng sổ gần ngày lưu kho
        // nhất. Cách này tránh sheet lịch sử đứng trước ghi nhầm metadata cho năm hiện tại.
        foreach ($documents as $document) {
            $archiveDate = $this->documentLedgerDate($document, $direction);
            $selection = $this->matcher->find(
                $ledgerIndex,
                (string) $document->document_code,
                $archiveDate,
                $direction,
            );
            $row = $selection['row'];

            if ($row === null) {
                $key = $selection['ambiguous'] ? 'ambiguous' : 'unmatched';
                $stats[$key]++;
                if (count($stats['unresolved']) < 20) {
                    $stats['unresolved'][] = [
                        'status' => $selection['ambiguous'] ? 'Trùng nhiều dòng sổ' : 'Không tìm thấy trong sổ',
                        'sheet' => '',
                        'row' => '',
                        'document_code' => $document->document_code ?? '',
                    ];
                }

                continue;
            }

            $stats['matched']++;
            $targetDirection = ($row['_book'] ?? '') === 'decision' ? Document::DIRECTION_DECISION : $direction;
            $updates = $this->updatesFor($document, $row, $targetDirection, $overwrite, $reclassify);

            if ($updates === []) {
                $stats['unchanged']++;

                continue;
            }

            $stats['updated']++;
            $stats['fields'] += count($updates);

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($document, $updates, $row, $sourceName, $userId): void {
                $before = collect(array_keys($updates))
                    ->mapWithKeys(fn (string $field) => [$field => $this->comparableValue($document->{$field})])
                    ->all();

                $document->update($updates);

                DocumentLog::create([
                    'document_id' => $document->id,
                    'user_id' => $userId,
                    'action' => 'ledger_metadata_enriched',
                    'details' => [
                        'ledger' => $sourceName,
                        'sheet' => $row['_sheet'] ?? null,
                        'row' => $row['_row'] ?? null,
                        'before' => $before,
                        'after' => $updates,
                    ],
                ]);
            });
        }

        return $stats;
    }

    /**
     * @return array<string, mixed>
     */
    private function updatesFor(
        Document $document,
        array $row,
        string $targetDirection,
        bool $overwrite,
        bool $reclassify,
    ): array {
        $updates = [];
        $archiveImported = $document->logs->isNotEmpty();

        if ($reclassify && $document->direction !== $targetDirection) {
            $updates['direction'] = $targetDirection;

            if (in_array($targetDirection, [Document::DIRECTION_OUTGOING, Document::DIRECTION_DECISION], true)) {
                $updates['forwarded_date'] = $row['forwarded_date']
                    ?? $this->archiveDate($document)
                    ?? $this->comparableValue($document->received_date);
                $updates['received_date'] = null;
            }
        }

        foreach (self::METADATA_FIELDS as $field) {
            $newValue = $row[$field] ?? null;
            if ($newValue === null || $newValue === '') {
                continue;
            }

            $currentValue = $this->comparableValue($document->{$field});
            $archiveDate = $archiveImported ? $this->archiveDate($document) : null;
            $mayRepairLegacyIssuedDate = $field === 'issued_date'
                && $archiveImported
                && $targetDirection === Document::DIRECTION_INCOMING
                && ($currentValue === null || $currentValue === '' || $currentValue === $archiveDate);

            if ($overwrite || $mayRepairLegacyIssuedDate || $currentValue === null || $currentValue === '') {
                if ((string) $currentValue !== (string) $newValue) {
                    $updates[$field] = $newValue;
                }
            }
        }

        // Importer cũ từng ghi ngày folder vào issued_date. Với văn bản đến,
        // ngày folder phải là ngày đến; ưu tiên ngày đến từ sổ, rồi mới lấy từ đường dẫn kho.
        if ($archiveImported && $targetDirection === Document::DIRECTION_INCOMING && blank($document->received_date)) {
            $receivedDate = $row['received_date'] ?? $this->archiveDate($document);
            if ($receivedDate) {
                $updates['received_date'] = $receivedDate;
            }
        }

        return $updates;
    }

    private function documentLedgerDate(Document $document, string $direction): ?string
    {
        $value = in_array($direction, [Document::DIRECTION_OUTGOING, Document::DIRECTION_DECISION], true)
            ? $document->forwarded_date
            : $document->received_date;

        return $this->comparableValue($value) ?: $this->archiveDate($document);
    }

    private function archiveDate(Document $document): ?string
    {
        foreach ($document->attachments as $attachment) {
            $path = $attachment->archive_relative_path ?: $attachment->file_path;
            $date = $path ? DocumentStoragePath::dateFromArchivePath($path) : null;
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function comparableValue(mixed $value): mixed
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
    }
}
