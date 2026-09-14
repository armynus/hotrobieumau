<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLedgerEntry;
use App\Models\DocumentLog;
use App\Models\User;
use App\Support\DocumentCode;
use App\Support\DocumentLedgerNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DocumentLedgerService
{
    public function nextNumber(int $branchId, int $year, string $book): int
    {
        return max(
            (int) DB::table('document_ledger_sequences')->where(compact('year', 'book'))->where('branch_id', $branchId)->value('last_number'),
            (int) DocumentLedgerEntry::where(compact('year', 'book'))->where('branch_id', $branchId)->max('sequence_number'),
        ) + 1;
    }

    /** Cấp số trong transaction, khóa theo chi nhánh/năm/sổ để hai văn thư không nhận trùng số. */
    public function register(Document $document, User $user, array $data): DocumentLedgerEntry
    {
        abort_unless($user->isClerk() && $user->branch_id, 403);
        $data = Validator::make($data, [
            'book' => 'required|in:incoming,outgoing,decision',
            'year' => 'required|integer|min:2000|max:2100',
            'number' => 'nullable|string|max:50',
            'registered_date' => 'nullable|date_format:Y-m-d',
            'document_code' => 'nullable|string|max:255',
            'source_name' => 'nullable|string|max:255',
            'source_sheet' => 'nullable|string|max:255',
            'source_row' => 'nullable|integer|min:1',
            'source_fingerprint' => 'nullable|string|size:64',
        ])->validate();
        if (! empty($data['registered_date']) && (int) substr($data['registered_date'], 0, 4) !== (int) $data['year']) {
            throw ValidationException::withMessages(['year' => 'Năm sổ phải trùng năm của ngày đến/ngày chuyển.']);
        }

        return DB::transaction(function () use ($document, $user, $data) {
            $scope = ['branch_id' => $user->branch_id, 'year' => $data['year'], 'book' => $data['book']];
            DB::table('document_ledger_sequences')->insertOrIgnore($scope + ['last_number' => 0]);
            $sequence = DB::table('document_ledger_sequences')->where($scope)->lockForUpdate()->first();
            // Cùng văn bản ở một chi nhánh chỉ có một dòng sổ, kể cả khi đổi năm/nhóm.
            Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $entry = DocumentLedgerEntry::where('document_id', $document->id)->where('branch_id', $user->branch_id)->first();
            $number = trim((string) ($data['number'] ?? ''));
            if ($number === '') {
                $number = (string) max($sequence->last_number + 1, $this->nextNumber((int) $user->branch_id, (int) $data['year'], $data['book']));
            }
            $key = DocumentLedgerNumber::normalize($number);
            // Số nhập tay được phép trùng để phản ánh nguyên sổ giấy/Excel.
            // Số tự cấp vẫn tăng dưới khóa sequence, không lấy lại số đã dùng.

            $code = $data['document_code'] ?? $document->document_code;
            if ($data['book'] !== 'incoming') {
                if (str_starts_with(trim((string) $code), '/')) {
                    $code = $number.trim($code);
                    if ((int) $document->managing_branch_id === (int) $user->branch_id) {
                        $document->update(['document_code' => $code]);
                    }
                }
                $codeNumber = DocumentLedgerNumber::fromCode($code);
                if ($codeNumber === null || DocumentLedgerNumber::normalize($codeNumber) !== $key) {
                    throw ValidationException::withMessages(['document_code' => 'Số đi phải trùng phần đầu trước dấu / của Số, ký hiệu văn bản. Ví dụ: '.$number.'/NHNo.ĐT-TH.']);
                }
            }
            $entry ??= new DocumentLedgerEntry(['document_id' => $document->id, 'branch_id' => $user->branch_id]);
            $entry->fill($data + ['registered_by' => $user->id]);
            $entry->fill(['number' => $number, 'number_key' => $key, 'sequence_number' => DocumentLedgerNumber::sequence($number), 'document_code' => $code, 'code_key' => DocumentCode::normalize($code)]);
            $changed = $entry->getDirty();
            $entry->save();
            DB::table('document_ledger_sequences')->where($scope)->update([
                'last_number' => max($sequence->last_number, $entry->sequence_number),
            ]);
            if ($data['book'] === 'incoming' && (int) $document->managing_branch_id === (int) $user->branch_id) {
                $document->update(['registry_number' => $number]);
            }
            if ($changed !== []) {
                DocumentLog::create([
                    'document_id' => $document->id, 'user_id' => $user->id, 'action' => 'ledger_registered',
                    'details' => ['branch_id' => $user->branch_id, 'year' => $entry->year, 'book' => $entry->book, 'number' => $number],
                ]);
            }

            return $entry;
        }, 3);
    }
}
