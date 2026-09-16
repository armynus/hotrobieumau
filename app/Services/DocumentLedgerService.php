<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLedgerEntry;
use App\Models\User;
use App\Support\DocumentCode;
use App\Support\DocumentLedgerNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Chỉ ghi bảng sổ và dãy số; tuyệt đối không ghi documents, file hay thông báo. */
class DocumentLedgerService
{
    public function nextNumber(int $branchId, int $year, string $book): int
    {
        return max(
            (int) DB::table('document_ledger_sequences')->where(compact('year', 'book'))->where('branch_id', $branchId)->value('last_number'),
            (int) DocumentLedgerEntry::where(compact('year', 'book'))->where('branch_id', $branchId)->max('sequence_number'),
        ) + 1;
    }

    /** Sao chép thông tin một lần khi người dùng chủ động đưa văn bản vào sổ. */
    public function register(Document $document, User $user, array $data): DocumentLedgerEntry
    {
        abort_unless($user->isClerk() && $user->branch_id, 403);

        return DB::transaction(function () use ($document, $user, $data) {
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $ownBranch = (int) $document->managing_branch_id === (int) $user->branch_id;
            abort_unless($ownBranch || ($document->visibility !== Document::VISIBILITY_PRIVATE
                && $document->activeTransfers()->where('to_branch_id', $user->branch_id)->exists()), 403);
            if (! $ownBranch && ($data['book'] ?? '') !== 'incoming') {
                throw ValidationException::withMessages(['book' => 'Văn bản nhận từ chi nhánh khác chỉ được đưa vào sổ đến.']);
            }
            if (DocumentLedgerEntry::where('branch_id', $user->branch_id)->where('document_id', $document->id)->exists()) {
                throw ValidationException::withMessages(['document' => 'Văn bản đã vào sổ chi nhánh mình. Dùng nút bút chì để chỉnh dòng sổ.']);
            }
            $snapshot = $document->only(DocumentLedgerEntry::METADATA_FIELDS);
            foreach (['issued_date', 'forwarded_date'] as $field) {
                $snapshot[$field] = $document->{$field}?->format('Y-m-d');
            }

            return $this->save($user, array_merge($snapshot, ['document_code' => $document->document_code], $data), null, $document->id);
        });
    }

    public function save(User $user, array $data, ?DocumentLedgerEntry $entry = null, ?int $sourceDocumentId = null): DocumentLedgerEntry
    {
        return $this->persist($user, $data, $entry, $sourceDocumentId, false);
    }

    /** Chỉ luồng import đã kiểm tra từng dòng mới được giữ ký hiệu thiếu/lỗi. */
    public function saveImportedRow(User $user, array $data, ?DocumentLedgerEntry $entry = null): DocumentLedgerEntry
    {
        Validator::make($data, ['number' => 'required|string', 'source_sheet' => 'required|string', 'source_row' => 'required|integer|min:1'])->validate();

        return $this->persist($user, $data, $entry, null, true);
    }

    private function persist(User $user, array $data, ?DocumentLedgerEntry $entry, ?int $sourceDocumentId, bool $imported): DocumentLedgerEntry
    {
        abort_unless($user->isClerk() && $user->branch_id, 403);
        if ($entry) {
            abort_unless((int) $entry->branch_id === (int) $user->branch_id, 403);
        }
        $rules = [
            'book' => 'required|in:incoming,outgoing,decision', 'year' => 'required|integer|min:2000|max:2100',
            'number' => 'nullable|string|max:50', 'registered_date' => 'nullable|date_format:Y-m-d', 'document_code' => 'nullable|string|max:255',
            'source_name' => 'nullable|string|max:255', 'source_sheet' => 'nullable|string|max:255',
            'source_row' => 'nullable|integer|min:1', 'source_fingerprint' => 'nullable|string|size:64',
            'title' => 'nullable|string|max:5000', 'issued_date' => 'nullable|date_format:Y-m-d',
            'forwarded_date' => 'nullable|date_format:Y-m-d', 'issuing_agency' => 'nullable|string|max:255',
            'signer' => 'nullable|string|max:255', 'recipient' => 'nullable|string|max:5000',
            'archive_recipient' => 'nullable|string|max:5000', 'copy_count' => 'nullable|integer|min:1|max:100000',
            'receipt_signature' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:5000',
        ];
        $data = Validator::make($data, $rules)->validate();
        if (! empty($data['registered_date']) && (int) substr($data['registered_date'], 0, 4) !== (int) $data['year']) {
            throw ValidationException::withMessages(['year' => 'Năm sổ phải trùng năm của ngày đến/ngày chuyển.']);
        }

        return DB::transaction(function () use ($user, $data, $entry, $sourceDocumentId, $imported) {
            $scope = ['branch_id' => $user->branch_id, 'year' => $data['year'], 'book' => $data['book']];
            DB::table('document_ledger_sequences')->insertOrIgnore($scope + ['last_number' => 0]);
            $sequence = DB::table('document_ledger_sequences')->where($scope)->lockForUpdate()->first();
            if ($entry) {
                $entry = DocumentLedgerEntry::where('branch_id', $user->branch_id)->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            }
            $code = $data['document_code'] ?? $entry?->document_code;
            $number = trim((string) ($data['number'] ?? ''));
            if ($number === '' && $data['book'] !== 'incoming') {
                $number = (string) DocumentLedgerNumber::fromCode($code);
            }
            if ($number === '') {
                $number = (string) max($sequence->last_number + 1, $this->nextNumber((int) $user->branch_id, (int) $data['year'], $data['book']));
            }
            $key = DocumentLedgerNumber::normalize($number);
            if ($data['book'] !== 'incoming') {
                if (! $imported && str_starts_with(trim((string) $code), '/')) {
                    $code = $number.trim($code);
                }
                $codeNumber = DocumentLedgerNumber::fromCode($code);
                if (($codeNumber === null && ! $imported) || ($codeNumber !== null && DocumentLedgerNumber::normalize($codeNumber) !== $key)) {
                    throw ValidationException::withMessages(['document_code' => 'Số sổ phải trùng số ở đầu số, ký hiệu văn bản, ví dụ 201-202 hoặc 1140.']);
                }
                if (array_key_exists('registered_date', $data)) {
                    $data['forwarded_date'] = $data['registered_date'];
                }
            }
            $entry ??= new DocumentLedgerEntry(['branch_id' => $user->branch_id, 'document_id' => $sourceDocumentId]);
            $entry->fill($data);
            $entry->fill(['number' => $number, 'number_key' => $key, 'sequence_number' => DocumentLedgerNumber::sequence($number),
                'document_code' => $code, 'code_key' => DocumentCode::normalize($code), 'registered_by' => $user->id]);
            $entry->save();
            DB::table('document_ledger_sequences')->where($scope)->update(['last_number' => max($sequence->last_number, DocumentLedgerNumber::lastSequence($number))]);

            return $entry;
        }, 3);
    }
}
