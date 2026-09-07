<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\User;
use App\Services\DocumentLedgerMatcher;
use App\Services\DocumentLedgerReader;
use App\Services\DocumentMetadataQueueService;
use App\Services\DocumentService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class ImportDocumentArchive extends Command
{
    protected $signature = 'documents:import-archive
        {source : Đường dẫn thư mục lưu trữ cũ hoặc ổ dùng chung}
        {--user= : ID văn thư Chi nhánh loại I đứng tên nhập}
        {--direction=auto : auto, incoming hoặc outgoing}
        {--ledger= : File sổ Excel cho chế độ incoming/outgoing cũ}
        {--incoming-ledger= : Sổ văn bản đến dùng khi kho chứa chung hai loại}
        {--outgoing-ledger= : Sổ văn bản đi dùng khi kho chứa chung hai loại}
        {--unmatched=skip : Với file không có trong sổ: skip, incoming hoặc outgoing}
        {--queue-ocr-missing : Xếp hàng OCR cho PDF không khớp/thiếu metadata sau khi nhập}
        {--visibility=branch : branch, private hoặc system}
        {--date-field=auto : auto, issued, received, forwarded hoặc both}
        {--extensions=pdf,doc,docx,xls,xlsx,ppt,pptx : Danh sách phần mở rộng được nhập}
        {--fallback-mtime : Dùng ngày sửa file nếu không tìm thấy folder NGAY dd-mm-yyyy}
        {--limit= : Giới hạn số file cần xử lý để chạy thử theo lô}
        {--dry-run : Chỉ quét và báo kết quả, không sao chép hay ghi database}
        {--yes : Xác nhận nhập thật không cần hỏi lại}';

    protected $description = 'Nhập kho văn bản chung và tự phân loại đến/đi bằng hai sổ Excel';

    public function handle(
        DocumentService $documentService,
        DocumentLedgerReader $ledgerReader,
        DocumentLedgerMatcher $ledgerMatcher,
        DocumentMetadataQueueService $metadataQueue,
    ): int {
        $source = realpath((string) $this->argument('source'));
        if ($source === false || ! is_dir($source) || ! is_readable($source)) {
            $this->error('Không đọc được thư mục nguồn. Kiểm tra lại đường dẫn và quyền truy cập ổ dùng chung.');

            return self::FAILURE;
        }

        $user = User::with('branch')->find((int) $this->option('user'));
        if (! $user || ! $user->canUploadDocument()) {
            $eligibleUsers = User::with('branch')
                ->where('document_role', 'clerk')
                ->get()
                ->filter(fn (User $candidate) => $candidate->canUploadDocument())
                ->map(fn (User $candidate) => [
                    $candidate->id,
                    $candidate->name,
                    $candidate->branch?->branch_name ?: '---',
                ])
                ->values()
                ->all();

            $this->error('Phải truyền --user là ID văn thư thuộc Chi nhánh loại I.');
            if ($eligibleUsers !== []) {
                $this->table(['ID', 'Văn thư', 'Chi nhánh'], $eligibleUsers);
            } else {
                $this->warn('Hiện chưa có tài khoản nào đủ điều kiện.');
            }

            return self::FAILURE;
        }

        $visibility = (string) $this->option('visibility');
        if (! in_array($visibility, ['private', 'branch', 'system'], true)) {
            $this->error('--visibility chỉ nhận private, branch hoặc system.');

            return self::FAILURE;
        }

        $direction = (string) $this->option('direction');
        if (! in_array($direction, ['auto', Document::DIRECTION_INCOMING, Document::DIRECTION_OUTGOING], true)) {
            $this->error('--direction chỉ nhận auto, incoming hoặc outgoing.');

            return self::FAILURE;
        }

        $unmatchedDirection = (string) $this->option('unmatched');
        if (! in_array($unmatchedDirection, ['skip', Document::DIRECTION_INCOMING, Document::DIRECTION_OUTGOING], true)) {
            $this->error('--unmatched chỉ nhận skip, incoming hoặc outgoing.');

            return self::FAILURE;
        }

        $dateField = (string) $this->option('date-field');
        if (! in_array($dateField, ['auto', 'issued', 'received', 'forwarded', 'both'], true)) {
            $this->error('--date-field chỉ nhận auto, issued, received, forwarded hoặc both.');

            return self::FAILURE;
        }

        if ($direction === 'auto' && $this->option('ledger')) {
            $this->error('Chế độ auto phải dùng --incoming-ledger và/hoặc --outgoing-ledger thay cho --ledger.');

            return self::FAILURE;
        }

        $ledgerPaths = [];
        $ledgerIndexes = [];
        $ledgerOptions = $direction === 'auto'
            ? [
                Document::DIRECTION_INCOMING => $this->option('incoming-ledger'),
                Document::DIRECTION_OUTGOING => $this->option('outgoing-ledger'),
            ]
            : [
                $direction => $this->option('ledger')
                    ?: ($direction === Document::DIRECTION_INCOMING
                        ? $this->option('incoming-ledger')
                        : $this->option('outgoing-ledger')),
            ];

        foreach ($ledgerOptions as $ledgerDirection => $ledgerOption) {
            if (! $ledgerOption) {
                continue;
            }

            $path = realpath((string) $ledgerOption);
            if ($path === false || ! is_file($path) || ! is_readable($path)) {
                $this->error('Không đọc được sổ Excel '.$ledgerDirection.': '.$ledgerOption);

                return self::FAILURE;
            }

            try {
                $ledgerPaths[$ledgerDirection] = $path;
                $ledgerIndexes[$ledgerDirection] = $ledgerMatcher->index($ledgerReader->read($path, $ledgerDirection));
            } catch (Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        }

        if ($direction === 'auto' && $ledgerIndexes === [] && $unmatchedDirection === 'skip') {
            $this->error('Kho chung cần ít nhất --incoming-ledger hoặc --outgoing-ledger để tự phân loại.');

            return self::FAILURE;
        }

        $extensions = collect(explode(',', (string) $this->option('extensions')))
            ->map(fn ($extension) => mb_strtolower(trim($extension)))
            ->filter()
            ->unique()
            ->all();
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('yes') && ! $this->confirm('Tiến hành sao chép file và tạo dữ liệu văn bản?')) {
            $this->warn('Đã hủy, chưa có dữ liệu nào được thay đổi.');

            return self::SUCCESS;
        }

        $stats = [
            'found' => 0,
            'imported' => 0,
            'duplicate' => 0,
            'undated' => 0,
            'failed' => 0,
            'ledger_matched' => 0,
            'ledger_unmatched' => 0,
            'ledger_ambiguous' => 0,
            'incoming' => 0,
            'outgoing' => 0,
            'skipped_classification' => 0,
        ];
        $previewRows = [];
        $importedDocumentIds = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile() || ! in_array(mb_strtolower($file->getExtension()), $extensions, true)) {
                    continue;
                }

                if ($limit !== null && $stats['found'] >= $limit) {
                    break;
                }

                $stats['found']++;
                $relativePath = $this->relativePath($source, $file->getPathname());
                $archiveDate = $this->extractArchiveDate($file->getPath());
                $documentCode = $this->documentCodeFromFileName($file->getFilename());

                if ($documentCode === '') {
                    $stats['failed']++;
                    $this->error('Bỏ qua vì tên file không tạo được số, ký hiệu: '.$relativePath);

                    continue;
                }

                if (! $archiveDate && $this->option('fallback-mtime')) {
                    $archiveDate = CarbonImmutable::createFromTimestamp($file->getMTime())->startOfDay();
                }

                if (! $archiveDate) {
                    $stats['undated']++;
                    $this->warn('Bỏ qua vì không xác định được ngày: '.$relativePath);

                    continue;
                }

                $classification = $this->classifyDocument(
                    $documentCode,
                    $archiveDate->format('Y-m-d'),
                    $direction,
                    $unmatchedDirection,
                    $ledgerIndexes,
                    $ledgerMatcher,
                );
                if ($classification['direction'] === null) {
                    $stats['skipped_classification']++;
                    $stats[$classification['ambiguous'] ? 'ledger_ambiguous' : 'ledger_unmatched']++;
                    $this->warn('Bỏ qua vì không xác định chắc văn bản đến/đi: '.$relativePath);

                    continue;
                }

                $fileDirection = $classification['direction'];
                $ledgerResult = [
                    'row' => $classification['row'],
                    'ambiguous' => $classification['ambiguous'],
                ];
                $this->countLedgerResult($stats, $classification['has_ledger'], $ledgerResult);

                // Giữ nguyên công thức key của kho đến đã nhập trước đây để chạy lại
                // không tạo bản sao. Chỉ thêm direction cho kho văn bản đi.
                $legacySourceKeyParts = [
                    $user->branch_id,
                    $archiveDate->format('Y-m-d'),
                    mb_strtolower($file->getFilename()),
                ];
                $sourceKeyParts = $legacySourceKeyParts;
                if ($fileDirection === Document::DIRECTION_OUTGOING) {
                    $sourceKeyParts[] = $fileDirection;
                }
                $sourceKey = hash('sha256', implode('|', $sourceKeyParts));
                $legacySourceKey = hash('sha256', implode('|', $legacySourceKeyParts));

                if (DocumentAttachment::whereIn('archive_source_key', array_unique([$sourceKey, $legacySourceKey]))->exists()) {
                    $stats['duplicate']++;

                    continue;
                }

                $stats[$fileDirection]++;

                if ($dryRun) {
                    if (count($previewRows) < 20) {
                        $previewRows[] = [
                            $archiveDate->format('d/m/Y'),
                            $fileDirection === Document::DIRECTION_OUTGOING ? 'Đi' : 'Đến',
                            $documentCode,
                            $ledgerResult['row']['title'] ?? '—',
                            $file->getFilename(),
                            $relativePath,
                        ];
                    }
                    $stats['imported']++;

                    continue;
                }

                try {
                    $date = $archiveDate->format('Y-m-d');
                    $data = array_merge(
                        $this->ledgerMetadata($ledgerResult['row'] ?? null),
                        array_filter(
                            $this->archiveDateFields($date, $fileDirection, $dateField),
                            fn ($value) => $value !== null
                        ),
                        [
                            // Tên file vẫn là số/ký hiệu chuẩn của kho; sổ chỉ bổ sung
                            // các trường nghiệp vụ, không đổi tên nhận diện của file.
                            'direction' => $fileDirection,
                            'document_code' => $documentCode,
                            'visibility' => $visibility,
                            '_ledger_source' => isset($ledgerPaths[$fileDirection]) ? basename($ledgerPaths[$fileDirection]) : null,
                            '_ledger_sheet' => $ledgerResult['row']['_sheet'] ?? null,
                            '_ledger_row' => $ledgerResult['row']['_row'] ?? null,
                        ]);

                    $document = $documentService->importArchivedFile(
                        $data,
                        $file->getPathname(),
                        $relativePath,
                        $sourceKey,
                        hash_file('sha256', $file->getPathname()),
                        $user
                    );
                    if (blank($document->issued_date) || blank($document->title)) {
                        $importedDocumentIds[] = (int) $document->id;
                    }
                    $stats['imported']++;
                    $this->line('Đã nhập: '.$relativePath);
                } catch (Throwable $exception) {
                    $stats['failed']++;
                    $this->error('Lỗi '.$relativePath.': '.$exception->getMessage());
                }
            }
        } catch (Throwable $exception) {
            $this->error('Không thể quét thư mục nguồn: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($dryRun && $previewRows !== []) {
            $this->table(['Ngày kho', 'Loại', 'Số, ký hiệu', 'Trích yếu từ sổ', 'Tên file', 'Đường dẫn tương đối (tối đa 20 dòng)'], $previewRows);
        }

        $this->newLine();
        $this->table(['Đã quét', $dryRun ? 'Có thể nhập' : 'Đã nhập', 'Đến', 'Đi', 'Trùng', 'Bỏ qua phân loại', 'Không có ngày', 'Lỗi'], [[
            $stats['found'], $stats['imported'], $stats['incoming'], $stats['outgoing'], $stats['duplicate'],
            $stats['skipped_classification'], $stats['undated'], $stats['failed'],
        ]]);

        if ($ledgerIndexes !== []) {
            $this->table(['Khớp sổ', 'Không thấy trong sổ', 'Trùng/mơ hồ'], [[
                $stats['ledger_matched'], $stats['ledger_unmatched'], $stats['ledger_ambiguous'],
            ]]);
        }

        if (! $dryRun && $this->option('queue-ocr-missing') && $importedDocumentIds !== []) {
            $capabilities = $metadataQueue->capabilities();
            $this->line($capabilities['message']);
            if ($capabilities['available']) {
                $queued = $metadataQueue->dispatchIds($importedDocumentIds);
                $this->info('Đã xếp hàng trích xuất/OCR '.$queued.' PDF còn thiếu metadata.');
            } else {
                $this->warn('Kho file vẫn đã nhập bình thường; chưa xếp OCR vì máy chủ thiếu engine.');
            }
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    public function extractArchiveDate(string $path): ?CarbonImmutable
    {
        $normalized = str_replace('\\', '/', $path);
        if (! preg_match_all('/(?:NGAY|NGÀY)?\s*(\d{1,2})[-_.\s]+(\d{1,2})[-_.\s]+(\d{4})(?:\/|$)/iu', $normalized.'/', $matches, PREG_SET_ORDER)) {
            return null;
        }

        $match = end($matches);
        try {
            $date = CarbonImmutable::createSafe((int) $match[3], (int) $match[2], (int) $match[1]);

            return $date?->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    public function documentCodeFromFileName(string $fileName): string
    {
        return trim(pathinfo($fileName, PATHINFO_FILENAME));
    }

    /**
     * @param  array<string, array<string, array<int, array<string, mixed>>>>  $ledgerIndexes
     * @return array{direction:?string, row:?array, ambiguous:bool, has_ledger:bool}
     */
    public function classifyDocument(
        string $documentCode,
        string $archiveDate,
        string $requestedDirection,
        string $unmatchedDirection,
        array $ledgerIndexes,
        DocumentLedgerMatcher $matcher,
    ): array {
        if ($requestedDirection !== 'auto') {
            $hasLedger = isset($ledgerIndexes[$requestedDirection]);
            $result = $hasLedger
                ? $matcher->find($ledgerIndexes[$requestedDirection], $documentCode, $archiveDate, $requestedDirection)
                : ['row' => null, 'ambiguous' => false];

            return [
                'direction' => $requestedDirection,
                'row' => $result['row'],
                'ambiguous' => $result['ambiguous'],
                'has_ledger' => $hasLedger,
            ];
        }

        $results = [];
        foreach ([Document::DIRECTION_INCOMING, Document::DIRECTION_OUTGOING] as $direction) {
            if (! isset($ledgerIndexes[$direction])) {
                continue;
            }
            $results[$direction] = $matcher->find($ledgerIndexes[$direction], $documentCode, $archiveDate, $direction);
        }

        $matches = array_filter($results, fn (array $result) => $result['row'] !== null);
        if (count($matches) === 1) {
            $direction = array_key_first($matches);

            return [
                'direction' => $direction,
                'row' => $matches[$direction]['row'],
                'ambiguous' => false,
                'has_ledger' => true,
            ];
        }

        if (count($matches) > 1) {
            $incomingExact = ($matches[Document::DIRECTION_INCOMING]['row']['received_date'] ?? null) === $archiveDate;
            $outgoingExact = ($matches[Document::DIRECTION_OUTGOING]['row']['forwarded_date'] ?? null) === $archiveDate;
            if ($incomingExact xor $outgoingExact) {
                $direction = $incomingExact ? Document::DIRECTION_INCOMING : Document::DIRECTION_OUTGOING;

                return [
                    'direction' => $direction,
                    'row' => $matches[$direction]['row'],
                    'ambiguous' => false,
                    'has_ledger' => true,
                ];
            }

            return ['direction' => null, 'row' => null, 'ambiguous' => true, 'has_ledger' => true];
        }

        if (collect($results)->contains(fn (array $result) => $result['ambiguous'])) {
            return ['direction' => null, 'row' => null, 'ambiguous' => true, 'has_ledger' => true];
        }

        return [
            'direction' => $unmatchedDirection === 'skip' ? null : $unmatchedDirection,
            'row' => null,
            'ambiguous' => false,
            'has_ledger' => $ledgerIndexes !== [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function archiveDateFields(string $date, string $direction, string $dateField = 'auto'): array
    {
        if ($dateField === 'auto') {
            return $direction === Document::DIRECTION_OUTGOING
                ? ['issued_date' => null, 'received_date' => null, 'forwarded_date' => $date]
                : ['issued_date' => null, 'received_date' => $date, 'forwarded_date' => null];
        }

        return [
            'issued_date' => in_array($dateField, ['issued', 'both'], true) ? $date : null,
            'received_date' => in_array($dateField, ['received', 'both'], true) ? $date : null,
            'forwarded_date' => $dateField === 'forwarded' ? $date : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ledgerMetadata(?array $row): array
    {
        if ($row === null) {
            return ['title' => null];
        }

        return collect($row)
            ->only([
                'registry_number', 'title', 'issued_date', 'received_date', 'forwarded_date',
                'issuing_agency', 'signer', 'recipient', 'archive_recipient', 'copy_count',
                'receipt_signature', 'notes',
            ])
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();
    }

    /**
     * @param  array<string, int>  $stats
     * @param  array{row:?array, ambiguous:bool}  $result
     */
    private function countLedgerResult(array &$stats, bool $hasLedger, array $result): void
    {
        if (! $hasLedger) {
            return;
        }

        if ($result['row'] !== null) {
            $stats['ledger_matched']++;
        } elseif ($result['ambiguous']) {
            $stats['ledger_ambiguous']++;
        } else {
            $stats['ledger_unmatched']++;
        }
    }

    private function relativePath(string $root, string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $path = str_replace('\\', '/', $path);

        return ltrim(substr($path, strlen($root)), '/');
    }
}
