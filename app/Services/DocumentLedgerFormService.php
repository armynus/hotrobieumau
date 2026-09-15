<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLedgerEntry;
use App\Models\User;
use App\Support\DocumentLedgerNumber;
use Illuminate\Support\Facades\Validator;

/** Sổ có metadata riêng. Chỉnh sổ không sửa kho, kể cả dòng lấy từ kho. */
class DocumentLedgerFormService
{
    public const METADATA_FIELDS = DocumentLedgerEntry::METADATA_FIELDS;

    public function formData(Document $document, User $user): array
    {
        $own = (int) $document->managing_branch_id === (int) $user->branch_id;
        $book = $own && in_array($document->direction, ['outgoing', 'decision'], true) ? $document->direction : 'incoming';
        $date = $book === 'incoming' ? $document->received_date : $document->forwarded_date;
        $data = $document->only(self::METADATA_FIELDS);
        foreach (['issued_date', 'forwarded_date'] as $field) {
            $data[$field] = $document->{$field}?->format('Y-m-d');
        }

        return array_merge($data, ['entry_id' => null, 'document_id' => $document->id, 'book' => $book, 'year' => $date?->year ?? now()->year,
            'number' => $book === 'incoming' ? $document->registry_number : DocumentLedgerNumber::fromCode($document->document_code),
            'document_code' => $document->document_code, 'registered_date' => $date?->format('Y-m-d'), 'own_branch' => $own]);
    }

    public function entryData(DocumentLedgerEntry $entry): array
    {
        $data = $entry->only(array_merge(self::METADATA_FIELDS, ['document_id', 'book', 'year', 'number', 'document_code']));
        foreach (['issued_date', 'forwarded_date', 'registered_date'] as $field) {
            $data[$field] = $entry->{$field}?->format('Y-m-d');
        }

        return array_merge($data, ['entry_id' => $entry->id, 'own_branch' => true]);
    }

    public function save(User $user, ?Document $source, array $input, ?DocumentLedgerEntry $entry = null): DocumentLedgerEntry
    {
        abort_unless($user->isClerk() && $user->branch_id, 403);
        Validator::make($input, [
            'registered_date' => 'required|date_format:Y-m-d', 'document_code' => 'required|string|max:255',
            'title' => 'required|string|max:5000', 'issued_date' => 'required|date_format:Y-m-d',
        ], [
            'registered_date.required' => 'Vui lòng nhập ngày đến/ngày chuyển vào sổ.',
            'issued_date.required' => 'Vui lòng nhập ngày, tháng văn bản.',
            'title.required' => 'Vui lòng nhập tên loại và trích yếu nội dung văn bản.',
            'document_code.required' => 'Vui lòng nhập số, ký hiệu văn bản.',
        ])->validate();
        $service = app(DocumentLedgerService::class);

        return $source ? $service->register($source, $user, $input) : $service->save($user, $input, $entry);
    }
}
