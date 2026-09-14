<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLedgerEntry;
use App\Models\DocumentLog;
use App\Models\User;
use App\Support\DocumentLedgerNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Form ghi/chỉnh sổ: dùng chung dữ liệu văn bản, không sao chép file hay gửi thông báo. */
class DocumentLedgerFormService
{
    public const METADATA_FIELDS = [
        'title', 'issued_date', 'issuing_agency', 'signer', 'recipient',
        'forwarded_date', 'archive_recipient', 'copy_count', 'receipt_signature', 'notes',
    ];

    public function canEditMetadata(Document $document, User $user): bool
    {
        return (int) $document->managing_branch_id === (int) $user->branch_id
            && ($document->canBeEditedBy($user) || (int) ($document->attachments_count ?? $document->attachments()->count()) === 0);
    }

    public function formData(Document $document, User $user, ?DocumentLedgerEntry $entry = null): array
    {
        $metadata = $document->only(self::METADATA_FIELDS);
        foreach (['issued_date', 'forwarded_date'] as $date) {
            $metadata[$date] = $document->{$date}?->format('Y-m-d');
        }
        $ownBranch = (int) $document->managing_branch_id === (int) $user->branch_id;
        $book = $entry?->book ?? ($ownBranch && in_array($document->direction, ['outgoing', 'decision'], true) ? $document->direction : 'incoming');
        $date = $entry ? $entry->registered_date : ($book === 'incoming' ? $document->received_date : $document->forwarded_date);

        return array_merge($metadata, [
            'document_id' => $document->id, 'book' => $book,
            'year' => $entry?->year ?? $date?->year ?? now()->year,
            'number' => $entry?->number ?? ($book === 'incoming' ? $document->registry_number : DocumentLedgerNumber::fromCode($document->document_code)),
            'document_code' => $entry?->document_code ?? $document->document_code,
            'registered_date' => $date?->format('Y-m-d'),
            'own_branch' => $ownBranch, 'can_edit_metadata' => $this->canEditMetadata($document, $user),
        ]);
    }

    public function save(User $user, ?Document $document, array $input, string $operation = 'upsert'): DocumentLedgerEntry
    {
        abort_unless($user->isClerk() && $user->branch_id, 403);
        $data = Validator::make($input, [
            'book' => 'required|in:incoming,outgoing,decision',
            'year' => 'required|integer|min:2000|max:2100',
            'number' => 'nullable|string|max:50',
            'registered_date' => 'required|date_format:Y-m-d',
            'document_code' => 'required|string|max:255',
            'title' => 'sometimes|required|string|max:5000',
            'issued_date' => 'sometimes|required|date_format:Y-m-d',
            'issuing_agency' => 'nullable|string|max:255',
            'signer' => 'nullable|string|max:255',
            'recipient' => 'nullable|string|max:5000',
            'forwarded_date' => 'nullable|date_format:Y-m-d',
            'archive_recipient' => 'nullable|string|max:5000',
            'copy_count' => 'nullable|integer|min:1|max:100000',
            'receipt_signature' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
        ], [
            'registered_date.required' => 'Vui lòng nhập ngày đến/ngày chuyển vào sổ.',
            'registered_date.date_format' => 'Ngày vào sổ không hợp lệ.',
            'issued_date.required' => 'Vui lòng nhập ngày, tháng văn bản.',
            'issued_date.date_format' => 'Ngày văn bản không hợp lệ.',
            'title.required' => 'Vui lòng nhập tên loại và trích yếu nội dung văn bản.',
            'document_code.required' => 'Vui lòng nhập số, ký hiệu văn bản.',
        ])->validate();

        return DB::transaction(function () use ($user, $document, $data, $operation) {
            $created = $document === null;
            if ($document) {
                $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
                $ownBranch = (int) $document->managing_branch_id === (int) $user->branch_id;
                abort_unless($ownBranch || $document->transfers()->where('to_branch_id', $user->branch_id)->exists(), 403);
                // Kiểm tra lại dưới khóa: văn thư khác có thể vừa vào sổ sau lúc tìm kiếm.
                $alreadyRegistered = DocumentLedgerEntry::where('document_id', $document->id)
                    ->where('branch_id', $user->branch_id)->exists();
                if ($operation === 'register' && $alreadyRegistered) {
                    throw ValidationException::withMessages(['document' => 'Văn bản đã vào sổ chi nhánh mình. Hãy dùng nút bút chì tại dòng sổ để chỉnh sửa.']);
                }
                if ($operation === 'edit' && ! $alreadyRegistered) {
                    throw ValidationException::withMessages(['document' => 'Văn bản chưa có trong sổ chi nhánh mình. Hãy dùng chức năng Đưa văn bản vào sổ.']);
                }
                if (! $ownBranch && $data['book'] !== 'incoming') {
                    throw ValidationException::withMessages(['book' => 'Văn bản nhận từ chi nhánh khác chỉ được ghi sổ đến tại chi nhánh mình.']);
                }
            }
            $canEdit = $created || $this->canEditMetadata($document, $user);
            $metadata = array_intersect_key($data, array_flip(self::METADATA_FIELDS));
            if ($canEdit) {
                Validator::make($metadata, ['title' => 'required', 'issued_date' => 'required'], [
                    'title.required' => 'Vui lòng nhập tên loại và trích yếu nội dung văn bản.',
                    'issued_date.required' => 'Vui lòng nhập ngày, tháng văn bản.',
                ])->validate();
            } elseif ($metadata !== []) {
                // Không cho sửa metadata/file của người khác thông qua form ghi sổ.
                throw ValidationException::withMessages(['document' => 'Bạn chỉ được chỉnh thông tin vào sổ; không được sửa nội dung văn bản do người khác đăng tải.']);
            }
            if (! $canEdit && $data['book'] !== 'incoming' && str_starts_with(trim($data['document_code']), '/')) {
                throw ValidationException::withMessages(['document_code' => 'Hãy giữ số, ký hiệu đầy đủ. Bạn không được tự cấp lại mã văn bản do người khác đăng tải.']);
            }
            if ($created) {
                $document = new Document([
                    'created_by' => $user->id, 'managing_branch_id' => $user->branch_id,
                    'visibility' => Document::VISIBILITY_PRIVATE, 'priority' => 'normal', 'security_level' => 'normal',
                ]);
            }
            if ($canEdit) {
                $document->fill($metadata);
                $document->direction = $data['book'];
                $document->document_code = $data['document_code'];
                if ($data['book'] === 'incoming') {
                    $document->received_date = $data['registered_date'];
                } else {
                    $document->forwarded_date = $data['registered_date'];
                    $document->received_date = null;
                    $document->registry_number = null;
                }
                $document->save();
            }
            if ($data['book'] !== 'incoming' && blank($data['number'] ?? null)) {
                $data['number'] = DocumentLedgerNumber::fromCode($data['document_code']);
            }
            $entry = app(DocumentLedgerService::class)->register($document, $user, $data);
            DocumentLog::create([
                'document_id' => $document->id, 'user_id' => $user->id,
                'action' => $created ? 'ledger_recorded' : 'updated',
                'details' => ['message' => $created ? 'Ghi sổ thông tin, chưa đăng file.' : 'Chỉnh thông tin văn bản và sổ.', 'ledger_entry_id' => $entry->id],
            ]);

            return $entry;
        });
    }
}
