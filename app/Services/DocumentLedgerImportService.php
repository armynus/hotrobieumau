<?php

namespace App\Services;

use App\Models\DocumentLedgerEntry;
use App\Models\User;
use App\Support\DocumentCode;
use App\Support\DocumentLedgerNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Nhập từng dòng sổ trong giao dịch riêng; không gửi thông báo văn bản mới. */
class DocumentLedgerImportService
{
    private const FIELDS = [
        'registry_number', 'document_code', 'title', 'issued_date', 'received_date',
        'forwarded_date', 'issuing_agency', 'signer', 'recipient', 'archive_recipient',
        'copy_count', 'receipt_signature', 'notes',
    ];

    public function __construct(private readonly DocumentLedgerService $ledger) {}

    /** @return array{rows:int,rows_by_year:array,created:int,updated:int,unchanged:int,skipped:int,skipped_other_year:int,skipped_missing_code:int,conflicts:int,issues:array,issue_count:int} */
    public function import(array $rows, User $user, int $year, string $sourceName, bool $dryRun = false, bool $overwrite = false, bool $updateOnly = false): array
    {
        if (! $user->isClerk() || ! $user->branch_id || $year < 2000 || $year > 2100) {
            throw ValidationException::withMessages(['ledger' => 'Chỉ văn thư có chi nhánh được nhập sổ; năm phải từ 2000 đến 2100.']);
        }

        $lock = null;
        if (! $dryRun) {
            $lock = Cache::lock('document-ledger-import:branch:'.$user->branch_id, 3600);
            if (! $lock->get()) {
                throw ValidationException::withMessages(['ledger' => 'Chi nhánh đang nhập sổ. Vui lòng chờ tác vụ trước hoàn tất.']);
            }
        }

        try {
            return $this->run($rows, $user, $year, $sourceName, $dryRun, $overwrite, $updateOnly);
        } finally {
            $lock?->release();
        }
    }

    private function run(array $rows, User $user, int $year, string $sourceName, bool $dryRun, bool $overwrite, bool $updateOnly): array
    {
        $stats = [
            'rows' => count($rows), 'rows_by_year' => [],
            'created' => 0, 'updated' => 0, 'unchanged' => 0,
            'skipped' => 0, 'skipped_other_year' => 0, 'skipped_missing_code' => 0,
            'conflicts' => 0, 'issues' => [], 'issue_count' => 0,
        ];
        $sourceFingerprints = [];
        foreach ($rows as $row) {
            $sourceYear = $row['_year'] ?? 'unknown';
            $stats['rows_by_year'][$sourceYear] = ($stats['rows_by_year'][$sourceYear] ?? 0) + 1;
            $code = DocumentCode::normalize($row['document_code'] ?? null);
            // Dòng thiếu mã vẫn được thống kê nhưng không được ghép kho hoặc
            // tham gia kiểm tra trùng số, tránh chặn nhầm một dòng hợp lệ.
            if ((int) ($row['_year'] ?? 0) !== $year || $code === '') {
                continue;
            }
            try {
                $key = $this->rowKey($row);
            } catch (ValidationException) {
                continue; // Báo lỗi theo dòng ở vòng xử lý bên dưới.
            }
            $sourceFingerprints[$key.':'.$code][$this->fingerprint($row)] = true;
        }
        ksort($stats['rows_by_year']);

        $claimedEntries = [];
        foreach ($rows as $row) {
            if (! empty($row['_year']) && (int) $row['_year'] !== $year) {
                $stats['skipped']++;
                $stats['skipped_other_year']++;

                continue;
            }
            // Áp dụng cho cả chạy thử/nhập thật, tạo mới/cập nhật, giao diện/CLI.
            // Không lấy số đến hoặc trích yếu để tự bịa số, ký hiệu còn thiếu.
            if (DocumentCode::normalize($row['document_code'] ?? null) === '') {
                $stats['skipped_missing_code']++;
                $this->issue($stats, $row, 'skipped', 'Thiếu số, ký hiệu văn bản; bỏ qua, không tạo hoặc cập nhật dữ liệu.');

                continue;
            }
            $dateField = ($row['_book'] ?? '') === 'incoming' ? 'received_date' : 'forwarded_date';
            if (empty($row[$dateField])) {
                $this->issue($stats, $row, 'skipped', 'Thiếu hoặc sai '.($dateField === 'received_date' ? 'ngày đến' : 'ngày chuyển').'; chưa xác định được năm vào sổ.');

                continue;
            }
            if (blank($row['_number'] ?? null)) {
                $this->issue($stats, $row, 'skipped', 'Thiếu số đến hoặc không đọc được số sổ ở đầu số, ký hiệu văn bản.');

                continue;
            }
            try {
                $key = $this->rowKey($row);
            } catch (ValidationException $exception) {
                $this->issue($stats, $row, 'conflicts', $exception->validator->errors()->first());

                continue;
            }
            $fingerprint = $this->fingerprint($row);

            try {
                $result = DB::transaction(function () use ($row, $user, $year, $sourceName, $dryRun, $overwrite, $updateOnly, &$claimedEntries, $sourceFingerprints, $fingerprint, $key, $dateField): string {
                    $entries = DocumentLedgerEntry::query()->where('branch_id', $user->branch_id)
                        ->where('year', $year)->where('book', $row['_book'])
                        ->where('number_key', DocumentLedgerNumber::normalize((string) $row['_number']))
                        ->where('code_key', DocumentCode::normalize($row['document_code']))
                        ->when(! $dryRun, fn ($query) => $query->lockForUpdate())->orderBy('id')->get()
                        ->reject(fn ($entry) => isset($claimedEntries[$entry->id]));
                    // Mỗi dòng nguồn được giữ riêng, kể cả dòng giống hệt nhau.
                    // Nhập lại ưu tiên nội dung khớp, rồi vị trí nguồn, rồi ứng viên duy nhất.
                    $entry = $entries->firstWhere('source_fingerprint', $fingerprint);
                    // Dòng mới chèn phía trên không được chiếm dòng cũ còn khớp
                    // nguyên nội dung ở phía dưới, kể cả số/ký hiệu giống nhau.
                    $fallbackEntries = $entries->reject(fn ($entry) => $entry->source_fingerprint
                        && isset($sourceFingerprints[$key.':'.DocumentCode::normalize($row['document_code'])][$entry->source_fingerprint]));
                    $entry ??= $fallbackEntries->first(fn ($entry) => $entry->source_name === $sourceName
                            && $entry->source_sheet === ($row['_sheet'] ?? null)
                            && (int) $entry->source_row === (int) ($row['_row'] ?? 0));
                    if (! $entry && $fallbackEntries->count() === 1) {
                        $entry = $fallbackEntries->first();
                    }
                    if (! $entry && $fallbackEntries->count() > 1) {
                        throw ValidationException::withMessages(['ledger' => 'Có nhiều dòng sổ cũ cùng số và ký hiệu; cần giữ đúng sheet/dòng nguồn để cập nhật.']);
                    }
                    if ($entry) {
                        $claimedEntries[$entry->id] = true;
                    }
                    if (! $entry && $updateOnly) {
                        throw ValidationException::withMessages(['skip' => 'Chưa có dòng sổ khớp; chế độ chỉ cập nhật không tạo mới.']);
                    }
                    $created = ! $entry;
                    $changes = $this->metadataChanges($entry ?? new DocumentLedgerEntry, $row, $overwrite);
                    $entryChanged = ! $entry
                        || (string) $entry->registered_date?->format('Y-m-d') !== (string) $row[$dateField]
                        || $entry->source_fingerprint !== $fingerprint
                        || (filled($row['document_code'] ?? null) && (string) $entry->document_code !== (string) $row['document_code']);
                    if (! $created && $changes === [] && ! $entryChanged) {
                        return 'unchanged';
                    }
                    if ($dryRun) {
                        return $created ? 'created' : 'updated';
                    }

                    $savedEntry = $this->ledger->save($user, array_merge($changes, [
                        'book' => $row['_book'], 'year' => $year, 'number' => (string) $row['_number'],
                        'registered_date' => $row[$dateField],
                        'document_code' => $row['document_code'] ?? $entry?->document_code,
                        'source_name' => $sourceName, 'source_sheet' => $row['_sheet'] ?? null,
                        'source_row' => $row['_row'] ?? null,
                        'source_fingerprint' => $fingerprint,
                    ]), $entry);
                    $claimedEntries[$savedEntry->id] = true;

                    return $created ? 'created' : 'updated';
                }, 3);
                $stats[$result]++;
            } catch (ValidationException $exception) {
                $this->issue($stats, $row, isset($exception->errors()['skip']) ? 'skipped' : 'conflicts', implode(' ', array_merge(...array_values($exception->errors()))));
            }
        }

        return $stats;
    }

    private function metadataChanges(DocumentLedgerEntry $entry, array $row, bool $overwrite): array
    {
        $changes = [];
        foreach (DocumentLedgerEntry::METADATA_FIELDS as $field) {
            $next = $row[$field] ?? null;
            if ($next === null || $next === '') {
                continue;
            }
            $current = $entry->{$field};
            $current = $current instanceof \DateTimeInterface ? $current->format('Y-m-d') : $current;
            if (($overwrite || $current === null || $current === '') && (string) $current !== (string) $next) {
                $changes[$field] = $next;
            }
        }

        return $changes;
    }

    private function rowKey(array $row): string
    {
        return ($row['_book'] ?? '').':'.DocumentLedgerNumber::normalize((string) ($row['_number'] ?? ''));
    }

    private function fingerprint(array $row): string
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = $row[$field] ?? null;
        }

        return hash('sha256', json_encode($values, JSON_UNESCAPED_UNICODE));
    }

    private function issue(array &$stats, array $row, string $kind, string $message): void
    {
        $stats[$kind]++;
        $stats['issue_count']++;
        if (count($stats['issues']) < 100) {
            $stats['issues'][] = ['sheet' => $row['_sheet'] ?? '', 'row' => $row['_row'] ?? 0, 'message' => $message];
        }
    }
}
