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

        return DocumentLedgerEntry::query()->where('branch_id', $user->branch_id)->where('book', $book);
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
        $entries = $query->orderBy('sequence_number')->orderBy('id')->limit(50)->get();

        return ['total' => $total, 'matches' => $entries->map(function ($entry) {
            $data = $entry->only(DocumentLedgerEntry::METADATA_FIELDS);
            foreach (['issued_date', 'received_date', 'forwarded_date'] as $field) {
                $data[$field] = $entry->{$field}?->format('Y-m-d');
            }
            $data[$entry->book === 'incoming' ? 'received_date' : 'forwarded_date'] = $entry->registered_date?->format('Y-m-d');
            $data['document_code'] = $entry->document_code;
            $data['registry_number'] = $entry->book === 'incoming' ? $entry->number : null;

            return [
                'id' => $entry->id, 'number' => $entry->number, 'year' => $entry->year, 'book' => $entry->book,
                'source_sheet' => $entry->source_sheet, 'source_row' => $entry->source_row,
                'registered_date' => $entry->registered_date?->format('d/m/Y'),
                'data' => $data,
            ];
        })->all()];
    }

}
