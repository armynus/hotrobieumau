<?php

namespace App\Services;

use App\Models\DocumentLedgerEntry;
use App\Models\User;
use App\Support\DocumentCode;
use App\Support\DocumentLedgerNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/** Tra sổ trong chi nhánh; ID dòng sổ mới là định danh, số văn bản được phép trùng. */
class DocumentLedgerUploadService
{
    public function scope(User $user, string $book): Builder
    {
        abort_unless($user->canUploadDocument() && $user->branch_id, 403);

        return DocumentLedgerEntry::query()->where('branch_id', $user->branch_id)->where('book', $book)
            ->whereHas('document', fn ($query) => $query->where('managing_branch_id', $user->branch_id)
                ->where(fn ($owner) => $owner->where('created_by', $user->id)->orWhereDoesntHave('attachments')));
    }

    public function lookup(User $user, string $book, int $year, string $term): array
    {
        $code = DocumentCode::normalize($term);
        if ($code === '') {
            return ['matches' => [], 'total' => 0];
        }
        try {
            $number = DocumentLedgerNumber::normalize($term);
        } catch (ValidationException) {
            $number = null;
        }
        $query = $this->scope($user, $book)->where('year', $year)
            ->where(function ($query) use ($code, $number) {
                $query->where('code_key', $code);
                if ($number !== null) {
                    $query->orWhere('number_key', $number);
                }
            });
        $total = (clone $query)->count();
        $entries = $query->with('document.attachments:id,document_id')->orderBy('sequence_number')->orderBy('id')->limit(50)->get();

        return ['total' => $total, 'matches' => $entries->map(function ($entry) {
            $document = $entry->document;
            $data = $document->only([
                'title', 'document_type_id', 'issued_date', 'received_date', 'forwarded_date',
                'issuing_agency', 'signer', 'recipient', 'archive_recipient', 'copy_count',
                'receipt_signature', 'notes', 'priority', 'security_level',
            ]);
            foreach (['issued_date', 'received_date', 'forwarded_date'] as $field) {
                $data[$field] = $document->{$field}?->format('Y-m-d');
            }
            $data[$entry->book === 'incoming' ? 'received_date' : 'forwarded_date'] = $entry->registered_date?->format('Y-m-d');
            $data['document_code'] = $entry->document_code ?: $document->document_code;
            $data['registry_number'] = $entry->book === 'incoming' ? $entry->number : null;

            return [
                'id' => $entry->id, 'number' => $entry->number, 'year' => $entry->year, 'book' => $entry->book,
                'source_sheet' => $entry->source_sheet, 'source_row' => $entry->source_row,
                'registered_date' => $entry->registered_date?->format('d/m/Y'),
                'version' => $this->version($entry), 'data' => $data,
            ];
        })->all()];
    }

    public function selected(User $user, array $data): DocumentLedgerEntry
    {
        $entry = $this->scope($user, $data['direction'])->whereKey($data['_ledger_entry_id'])
            ->lockForUpdate()->firstOrFail();
        $entry->setRelation('document', $entry->document()->lockForUpdate()->firstOrFail());
        if (! hash_equals($this->version($entry), (string) ($data['_ledger_entry_version'] ?? ''))) {
            throw ValidationException::withMessages(['ledger_entry_id' => 'Dòng sổ vừa thay đổi. Hãy tra lại và chọn đúng dòng trước khi đăng tải.']);
        }
        $date = $data[$entry->book === 'incoming' ? 'received_date' : 'forwarded_date'] ?? '';
        $number = $entry->book === 'incoming' ? ($data['registry_number'] ?? '') : DocumentLedgerNumber::fromCode($data['document_code']);
        if (DocumentCode::normalize($data['document_code']) !== $entry->code_key
            || DocumentLedgerNumber::normalize((string) $number) !== $entry->number_key
            || (int) substr($date, 0, 4) !== $entry->year) {
            throw ValidationException::withMessages(['ledger_entry_id' => 'Số văn bản hoặc năm không khớp dòng sổ đã chọn. Hãy tra lại, không đăng vào dòng khác.']);
        }

        return $entry;
    }

    public function version(DocumentLedgerEntry $entry): string
    {
        $attachments = $entry->document->relationLoaded('attachments')
            ? $entry->document->attachments->count() : $entry->document->attachments()->count();

        return hash('sha256', json_encode([$entry->getAttributes(), $entry->document->getAttributes(), $attachments]));
    }
}
