<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentLedgerEntry;
use App\Models\DocumentLog;
use App\Models\DocumentPermission;
use App\Models\DocumentTransfer;
use App\Support\DocumentCode;
use App\Support\DocumentLedgerNumber;
use App\Support\DocumentStoragePath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    /**
     * Import one historical file without loading the whole file into memory.
     */
    public function importArchivedFile(array $data, string $sourcePath, string $relativePath, string $sourceKey, string $checksum, $user): Document
    {
        // Ngày trong đường dẫn kho cũ là mốc lưu trữ đáng tin cậy nhất. Nó cũng
        // cho phép lưu đúng folder khi văn bản chưa xác định là đến hay đi.
        $archiveDate = $data['_archive_date']
            ?? $data['received_date']
            ?? $data['forwarded_date']
            ?? $data['issued_date']
            ?? now()->toDateString();
        $folderPath = DocumentStoragePath::directoryForDate($archiveDate);
        $storedFileName = $this->uniqueStoredFileName($folderPath, basename($sourcePath));
        $storedPath = $folderPath.'/'.$storedFileName;
        $stream = fopen($sourcePath, 'rb');

        if ($stream === false) {
            throw new \RuntimeException('Không thể đọc file nguồn: '.$sourcePath);
        }

        try {
            if (! Storage::disk('public')->put($storedPath, $stream)) {
                throw new \RuntimeException('Không thể sao chép file vào kho lưu trữ: '.$storedFileName);
            }
        } finally {
            fclose($stream);
        }

        try {
            return DB::transaction(function () use ($data, $sourcePath, $relativePath, $sourceKey, $checksum, $user, $storedPath, $storedFileName) {
                $document = $this->archivedLedgerDocument($data, $user) ?? Document::create([
                    'direction' => ($data['_ledger_match']['_book'] ?? '') === 'decision' ? Document::DIRECTION_DECISION : ($data['direction'] ?? Document::DIRECTION_UNCLASSIFIED),
                    'registry_number' => $data['registry_number'] ?? null,
                    'document_code' => $data['document_code'],
                    'title' => $data['title'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'document_type_id' => $data['document_type_id'] ?? null,
                    'managing_branch_id' => $user->branch_id,
                    'issued_date' => $data['issued_date'] ?? null,
                    'received_date' => $data['received_date'] ?? null,
                    'forwarded_date' => $data['forwarded_date'] ?? null,
                    'issuing_agency' => $data['issuing_agency'] ?? null,
                    'signer' => $data['signer'] ?? null,
                    'recipient' => $data['recipient'] ?? null,
                    'archive_recipient' => $data['archive_recipient'] ?? null,
                    'copy_count' => $data['copy_count'] ?? null,
                    'receipt_signature' => $data['receipt_signature'] ?? null,
                    'priority' => 'normal',
                    'security_level' => 'normal',
                    'visibility' => $data['visibility'] ?? Document::VISIBILITY_PRIVATE,
                    'created_by' => $user->id,
                ]);

                DocumentAttachment::create([
                    'document_id' => $document->id,
                    'file_path' => $storedPath,
                    'file_name' => $storedFileName,
                    'file_extension' => Str::lower(pathinfo($storedFileName, PATHINFO_EXTENSION)) ?: null,
                    'mime_type' => mime_content_type($sourcePath) ?: 'application/octet-stream',
                    'file_size' => filesize($sourcePath) ?: null,
                    'uploaded_by' => $user->id,
                    'archive_source_key' => $sourceKey,
                    'archive_relative_path' => $relativePath,
                    'checksum_sha256' => $checksum,
                ]);

                DocumentLog::create([
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'action' => 'archive_imported',
                    'details' => [
                        'source_file' => $relativePath,
                        'source_key' => $sourceKey,
                        'checksum_sha256' => $checksum,
                        'ledger' => $data['_ledger_source'] ?? null,
                        'ledger_sheet' => $data['_ledger_sheet'] ?? null,
                        'ledger_row' => $data['_ledger_row'] ?? null,
                    ],
                ]);

                return $document->load('attachments');
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPath);
            throw $exception;
        }
    }

    /**
     * Tạo văn bản mới hoặc đăng file vào dòng sổ đã chọn, giữ nguyên ID cũ.
     */
    public function createDocument(array $data, array $files, $user, ?callable $afterSave = null)
    {
        $storedPaths = [];
        try {
            return DB::transaction(function () use ($data, $files, $user, $afterSave, &$storedPaths) {
                $renamedFiles = [];
                $entry = isset($data['_ledger_entry_id']) ? app(DocumentLedgerUploadService::class)->selected($user, $data) : null;
                $document = $entry?->document;
                $metadata = [
                    'direction' => $data['direction'] ?? Document::DIRECTION_INCOMING,
                    'registry_number' => $data['registry_number'] ?? null,
                    'document_code' => $data['document_code'] ?? null,
                    'title' => $data['title'],
                    'document_type_id' => $data['document_type_id'] ?? null,
                    'managing_branch_id' => $data['managing_branch_id'] ?? $user->branch_id,
                    'visibility' => $data['visibility'] ?? Document::VISIBILITY_PRIVATE,
                    'issued_date' => $data['issued_date'] ?? null,
                    'received_date' => $data['received_date'] ?? null,
                    'forwarded_date' => $data['forwarded_date'] ?? null,
                    'issuing_agency' => $data['issuing_agency'] ?? null,
                    'signer' => $data['signer'] ?? null,
                    'recipient' => $data['recipient'] ?? null,
                    'archive_recipient' => $data['archive_recipient'] ?? null,
                    'copy_count' => $data['copy_count'] ?? null,
                    'receipt_signature' => $data['receipt_signature'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'priority' => $data['priority'] ?? 'normal',
                    'security_level' => $data['security_level'] ?? 'normal',
                    'created_by' => $user->id,
                ];
                if ($document) {
                    // Giữ nguyên ID văn bản/file cũ; lần đầu đăng file xác lập người đăng tải.
                    $document->fill($metadata)->save();
                    $data['_ledger'] = [
                        'book' => $entry->book, 'year' => $entry->year, 'number' => $entry->number,
                        'document_code' => $data['document_code'],
                        'registered_date' => $data[$entry->book === 'incoming' ? 'received_date' : 'forwarded_date'],
                    ];
                } else {
                    $document = Document::create($metadata);
                }

                // Kiểm tra/cấp số trước khi ghi file để lỗi dữ liệu sổ không để lại file rác.
                if (isset($data['_ledger'])) {
                    app(DocumentLedgerService::class)->register($document, $user, $data['_ledger']);
                }

                // Handle file uploads
                foreach ($files as $file) {
                    if ($file) {
                        // Lưu theo ngày vào sổ: ngày đến đối với văn bản đến,
                        // ngày chuyển đối với văn bản đi; ngày văn bản là phương án dự phòng.
                        $ledgerDate = in_array($data['direction'] ?? '', [Document::DIRECTION_OUTGOING, Document::DIRECTION_DECISION], true)
                            ? ($data['forwarded_date'] ?? $data['issued_date'] ?? null)
                            : ($data['received_date'] ?? $data['issued_date'] ?? null);
                        $folderPath = DocumentStoragePath::directoryForDate($ledgerDate);

                        $originalFileName = $file->getClientOriginalName();
                        $storedFileName = $this->uniqueStoredFileName($folderPath, $originalFileName);
                        $path = $file->storeAs($folderPath, $storedFileName, 'public');

                        if ($path === false) {
                            throw new \RuntimeException('Không thể lưu file văn bản: '.$originalFileName);
                        }
                        $storedPaths[] = $path;

                        if ($storedFileName !== $this->sanitizeFileName($originalFileName)) {
                            $renamedFiles[] = [
                                'original' => $originalFileName,
                                'stored' => $storedFileName,
                            ];
                        }

                        DocumentAttachment::create([
                            'document_id' => $document->id,
                            'file_path' => $path,
                            'file_name' => $storedFileName,
                            'file_extension' => pathinfo($storedFileName, PATHINFO_EXTENSION) ?: null,
                            'mime_type' => $file->getMimeType(),
                            'file_size' => $file->getSize(),
                            'uploaded_by' => $user->id,
                        ]);
                    }
                }

                // Log creation
                DocumentLog::create([
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'action' => $entry ? ($files !== [] ? 'published' : 'updated') : 'created',
                    'details' => ['message' => 'Document created and files uploaded.'],
                ]);
                if ($afterSave) {
                    $afterSave($document);
                }

                return [
                    'document' => $document->load('attachments'),
                    'renamed_files' => $renamedFiles,
                ];
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }
    }

    /** Nếu đã nhập thông tin từ sổ trước, gắn file vào đúng dòng đó thay vì tạo văn bản thứ hai. */
    private function archivedLedgerDocument(array $data, $user): ?Document
    {
        $row = $data['_ledger_match'] ?? null;
        if (! $row || empty($row['_year']) || empty($row['_number']) || empty($row['_book'])) {
            return null;
        }
        try {
            $number = DocumentLedgerNumber::normalize((string) $row['_number']);
        } catch (\Illuminate\Validation\ValidationException) {
            return null;
        }
        $entries = DocumentLedgerEntry::where('branch_id', $user->branch_id)
            ->where('year', $row['_year'])->where('book', $row['_book'])->where('number_key', $number)
            ->where('code_key', DocumentCode::normalize($row['document_code'] ?? null))->get();
        if ($entries->count() > 1) {
            $exact = $entries->filter(fn ($entry) => $entry->source_sheet === ($row['_sheet'] ?? null)
                && (int) $entry->source_row === (int) ($row['_row'] ?? 0));
            if ($exact->count() !== 1) {
                throw new \RuntimeException('Có nhiều dòng sổ trùng số; hãy chọn đúng dòng và đăng file trên giao diện.');
            }
            $entries = $exact;
        }
        $entry = $entries->first();
        if (! $entry) {
            return null;
        }
        if (DocumentCode::normalize($entry->document_code) !== DocumentCode::normalize($row['document_code'] ?? null)) {
            throw new \RuntimeException('Số trong sổ đã thuộc văn bản khác. Kiểm tra lại dòng '.$row['_row'].' của sheet '.$row['_sheet'].'.');
        }

        $document = Document::whereKey($entry->document_id)->where('managing_branch_id', $user->branch_id)->lockForUpdate()->firstOrFail();
        // Khi sổ đã nhập trước file, cập nhật ngày/trích yếu từ dòng Excel đang đối chiếu.
        // Ô trống không xóa thông tin đã có; không dùng ngày folder đè ngày chính thức.
        $updates = array_filter(
            array_intersect_key($row, array_flip(['issued_date', 'title'])),
            fn ($value) => $value !== null && $value !== ''
        );
        if ($updates !== []) {
            $document->fill($updates)->save();
        }

        return $document;
    }

    /**
     * Keep the uploaded file name readable and append (1), (2), ... on collision.
     */
    private function uniqueStoredFileName(string $folderPath, string $originalFileName): string
    {
        $safeFileName = $this->sanitizeFileName($originalFileName);
        $extension = pathinfo($safeFileName, PATHINFO_EXTENSION);
        $baseName = pathinfo($safeFileName, PATHINFO_FILENAME);
        $counter = 0;

        do {
            $suffix = $counter > 0 ? ' ('.$counter.')' : '';
            $extensionWithDot = $extension !== '' ? '.'.$extension : '';
            $maxBaseBytes = max(1, 220 - strlen($suffix) - strlen($extensionWithDot));
            $trimmedBaseName = mb_strcut($baseName, 0, $maxBaseBytes, 'UTF-8');
            $candidate = $trimmedBaseName.$suffix.$extensionWithDot;
            $counter++;
        } while (Storage::disk('public')->exists($folderPath.'/'.$candidate));

        return $candidate;
    }

    /**
     * Remove path fragments and characters which are invalid in common filesystems.
     */
    private function sanitizeFileName(string $fileName): string
    {
        $fileName = basename(str_replace('\\', '/', $fileName));
        $fileName = preg_replace('/[\x00-\x1F\x7F<>:"\/\\\\|?*]+/u', '_', $fileName) ?? '';
        $fileName = trim($fileName, " .\t\n\r\0\x0B");

        if ($fileName === '') {
            return 'van-ban';
        }

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);

        if (preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/i', $baseName)) {
            $baseName = '_'.$baseName;
        }

        return $baseName.($extension !== '' ? '.'.$extension : '');
    }

    /**
     * Assign permission to a target (branch, department, position, user)
     */
    public function assignPermission(Document $document, string $targetType, int $targetId, $granter, string $permission = 'view')
    {
        return DocumentPermission::updateOrCreate([
            'document_id' => $document->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ], [
            'permission' => $permission,
            'granted_by' => $granter->id,
            'granted_at' => now(),
        ]);
    }

    /**
     * Transfer document from Branch A to Branch B
     */
    public function transferDocument(Document $document, int $fromBranchId, int $toBranchId, $transferer, ?string $note = null)
    {
        return DB::transaction(function () use ($document, $fromBranchId, $toBranchId, $transferer, $note) {
            $transfer = DocumentTransfer::updateOrCreate([
                'document_id' => $document->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'to_department_id' => null,
                'to_user_id' => null,
            ], [
                'transferred_by' => $transferer->id,
                'transferred_at' => now(),
                'note' => $note,
                'status' => 'pending',
            ]);

            DocumentLog::create([
                'document_id' => $document->id,
                'user_id' => $transferer->id,
                'action' => 'transferred',
                'details' => ['from_branch_id' => $fromBranchId, 'to_branch_id' => $toBranchId, 'note' => $note],
            ]);

            return $transfer;
        });
    }

    /**
     * Distribute a received document to a department in the clerk's branch.
     */
    public function transferDocumentToDepartment(Document $document, int $departmentId, $transferer, ?string $note = null)
    {
        return DB::transaction(function () use ($document, $departmentId, $transferer, $note) {
            $transfer = DocumentTransfer::updateOrCreate([
                'document_id' => $document->id,
                'from_branch_id' => $transferer->branch_id,
                'to_branch_id' => null,
                'to_department_id' => $departmentId,
                'to_user_id' => null,
            ], [
                'transferred_by' => $transferer->id,
                'transferred_at' => now(),
                'note' => $note,
                'status' => 'received',
            ]);

            $this->assignPermission($document, 'department', $departmentId, $transferer);

            DocumentLog::create([
                'document_id' => $document->id,
                'user_id' => $transferer->id,
                'action' => 'distributed_to_department',
                'details' => [
                    'from_branch_id' => $transferer->branch_id,
                    'to_department_id' => $departmentId,
                    'note' => $note,
                ],
            ]);

            return $transfer;
        });
    }

    /**
     * Update document metadata and record changed values in the audit log.
     */
    public function updateDocument(Document $document, array $data, $user): Document
    {
        return DB::transaction(function () use ($document, $data, $user) {
            $document->fill($data);
            $changes = $document->getDirty();
            $document->save();

            unset($changes['updated_at']);

            if (! empty($changes)) {
                DocumentLog::create([
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'action' => 'updated',
                    'details' => [
                        'changes' => $changes,
                    ],
                ]);
            }

            return $document->fresh(['attachments']);
        });
    }

    /**
     * Permanently delete a document, its relational data and stored files.
     * Files are staged first so a database failure can restore them.
     */
    public function deleteDocument(Document $document): void
    {
        $document->loadMissing('attachments');
        $paths = $document->attachments
            ->pluck('file_path')
            ->filter()
            ->unique()
            ->values();
        // Khu vực tạm nằm ngoài cây lưu trữ NAM/THANG/NGAY để folder văn bản luôn sạch.
        $stagingDirectory = 'document-deletion-staging/'.Str::uuid();
        $stagedFiles = [];

        try {
            foreach ($paths as $index => $originalPath) {
                if (! Storage::disk('public')->exists($originalPath)) {
                    continue;
                }

                $stagedPath = $stagingDirectory.'/'.$index.'-'.basename($originalPath);
                if (! Storage::disk('public')->move($originalPath, $stagedPath)) {
                    throw new \RuntimeException('Không thể chuẩn bị xóa file: '.basename($originalPath));
                }

                $stagedFiles[$originalPath] = $stagedPath;
            }

            DB::transaction(function () use ($document) {
                $document->delete();
            });
        } catch (\Throwable $exception) {
            foreach ($stagedFiles as $originalPath => $stagedPath) {
                if (Storage::disk('public')->exists($stagedPath)) {
                    Storage::disk('public')->move($stagedPath, $originalPath);
                }
            }
            Storage::disk('public')->deleteDirectory($stagingDirectory);
            throw $exception;
        }

        if (! Storage::disk('public')->deleteDirectory($stagingDirectory)) {
            report(new \RuntimeException('Không thể dọn thư mục file đã xóa: '.$stagingDirectory));
        }
    }

    /**
     * Mark document as read by user
     */
    public function markAsRead(Document $document, $user)
    {
        return $document->reads()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);
    }
}
