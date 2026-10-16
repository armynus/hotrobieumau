<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class EnrichDocumentMetadataFromScannedExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:enrich-from-scanned-excel
        {file=D:\Agribank văn bản\Ket_Qua_Quet_Van_Ban.xlsx : Đường dẫn file Excel kết quả quét}
        {--commit : Thực thi cập nhật thật vào database; mặc định là chạy thử nghiệm (dry-run)}
        {--user=3 : ID người dùng thực hiện (mặc định ID 3 - Văn thư)}
        {--backup : Tạo bảng sao lưu documents_backup trước khi cập nhật}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bổ sung metadata văn bản từ file Excel quét nhận diện (Phương án A: chỉ điền các trường còn trống)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = (string) $this->argument('file');
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            $this->error("Không tìm thấy hoặc không thể đọc file Excel tại: {$filePath}");

            return self::FAILURE;
        }

        $userId = (int) $this->option('user');
        $user = User::find($userId);
        if (! $user) {
            $this->error("Không tìm thấy người dùng có ID {$userId}.");

            return self::FAILURE;
        }

        $isCommit = (bool) $this->option('commit');
        $shouldBackup = $this->option('backup') || $isCommit;

        $this->info("Đang đọc file Excel: {$filePath}...");
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getSheetByName('Danh sách văn bản') ?: $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
        } catch (Throwable $e) {
            $this->error("Lỗi khi mở file Excel: " . $e->getMessage());

            return self::FAILURE;
        }

        $totalExcelRows = $highestRow - 1;
        $this->info("Đã nạp file Excel thành công: sheet '{$sheet->getTitle()}', tổng cộng {$totalExcelRows} dòng dữ liệu.");

        // Index attachments by relative storage path (NAM YYYY/...) and by filename
        $this->info('Đang nạp và lập chỉ mục danh sách file đính kèm trong cơ sở dữ liệu...');
        $attachments = DocumentAttachment::with('document')->get();
        $attByPath = [];
        $attByName = [];

        foreach ($attachments as $att) {
            $normPath = str_replace('\\', '/', $att->file_path);
            if (preg_match('#(NAM\s+\d{4}/.+)$#i', $normPath, $m)) {
                $pathKey = mb_strtolower(trim($m[1]));
                $attByPath[$pathKey][] = $att;
            }
            $fnKey = mb_strtolower(trim($att->file_name));
            $attByName[$fnKey][] = $att;
        }

        $stats = [
            'total_excel_rows' => $totalExcelRows,
            'matched' => 0,
            'unmatched' => 0,
            'ambiguous' => 0,
            'documents_to_update' => 0,
            'documents_unchanged' => 0,
            'fields_filled' => [
                'title' => 0,
                'direction' => 0,
                'issuing_agency' => 0,
                'issued_date' => 0,
                'document_code' => 0,
            ],
        ];

        $pendingUpdates = [];
        $samplePreview = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $stt = $sheet->getCell('A' . $row)->getValue();
            $pdfName = trim((string) $sheet->getCell('B' . $row)->getValue());
            $excelDocCode = trim((string) $sheet->getCell('C' . $row)->getValue());
            $excelDateRaw = trim((string) $sheet->getCell('D' . $row)->getFormattedValue());
            $excelAgency = trim((string) $sheet->getCell('E' . $row)->getValue());
            $excelTitle = trim((string) $sheet->getCell('F' . $row)->getValue());
            $excelCategory = trim((string) $sheet->getCell('G' . $row)->getValue());
            $excelPath = trim((string) $sheet->getCell('I' . $row)->getValue());

            // Match attachment by path
            $normEx = str_replace('\\', '/', $excelPath);
            $pathKey = null;
            if (preg_match('#(NAM\s+\d{4}/.+)$#i', $normEx, $m)) {
                $pathKey = mb_strtolower(trim($m[1]));
            }

            $candidates = $pathKey && isset($attByPath[$pathKey]) ? $attByPath[$pathKey] : [];
            if (empty($candidates)) {
                $fnKey = mb_strtolower($pdfName);
                $candidates = $attByName[$fnKey] ?? [];
            }

            if (count($candidates) === 1) {
                $stats['matched']++;
                $att = $candidates[0];
                $doc = $att->document;
                if (! $doc) {
                    $stats['unmatched']++;
                    continue;
                }

                $updates = [];
                $before = [];

                // 1. Title: chỉ điền khi đang trống
                $currentTitle = trim((string) $doc->title);
                if ($currentTitle === '' && $excelTitle !== '') {
                    $updates['title'] = $excelTitle;
                    $before['title'] = $doc->title;
                    $stats['fields_filled']['title']++;
                }

                // 2. Direction: chỉ điền khi đang unclassified hoặc rỗng
                $currentDirection = trim((string) $doc->direction);
                if ($currentDirection === '' || $currentDirection === Document::DIRECTION_UNCLASSIFIED) {
                    $mappedDirection = match ($excelCategory) {
                        'Văn bản đến' => Document::DIRECTION_INCOMING,
                        'Văn bản đi' => Document::DIRECTION_OUTGOING,
                        'Quyết định' => Document::DIRECTION_DECISION,
                        default => null,
                    };
                    if ($mappedDirection) {
                        $updates['direction'] = $mappedDirection;
                        $before['direction'] = $doc->direction;
                        $stats['fields_filled']['direction']++;
                    }
                }

                // 3. Issuing agency: chỉ điền khi đang trống
                $currentAgency = trim((string) $doc->issuing_agency);
                if ($currentAgency === '' && $excelAgency !== '') {
                    $updates['issuing_agency'] = $excelAgency;
                    $before['issuing_agency'] = $doc->issuing_agency;
                    $stats['fields_filled']['issuing_agency']++;
                }

                // 4. Issued date: chỉ điền khi đang trống
                if ($doc->issued_date === null && $excelDateRaw !== '') {
                    try {
                        $parsedDate = Carbon::createFromFormat('d/m/Y', $excelDateRaw)->format('Y-m-d');
                    } catch (Throwable) {
                        try {
                            $parsedDate = Carbon::parse($excelDateRaw)->format('Y-m-d');
                        } catch (Throwable) {
                            $parsedDate = null;
                        }
                    }
                    if ($parsedDate) {
                        $updates['issued_date'] = $parsedDate;
                        $before['issued_date'] = null;
                        $stats['fields_filled']['issued_date']++;
                    }
                }

                // 5. Document code: chỉ điền khi đang trống (an toàn)
                $currentCode = trim((string) $doc->document_code);
                if ($currentCode === '' && $excelDocCode !== '' && ! in_array(mb_strtolower($excelDocCode), ['không số', 'khong so'])) {
                    $updates['document_code'] = $excelDocCode;
                    $before['document_code'] = null;
                    $stats['fields_filled']['document_code']++;
                }

                if (! empty($updates)) {
                    $stats['documents_to_update']++;
                    $pendingUpdates[] = [
                        'document' => $doc,
                        'updates' => $updates,
                        'before' => $before,
                        'excel_row' => $row,
                        'excel_stt' => $stt,
                        'file_name' => $pdfName,
                    ];

                    if (count($samplePreview) < 10) {
                        $samplePreview[] = [
                            'ID' => $doc->id,
                            'File' => $pdfName,
                            'Các trường bổ sung' => implode(', ', array_keys($updates)),
                            'Trích yếu mới' => mb_strimwidth($updates['title'] ?? '(Giữ nguyên)', 0, 40, '...'),
                            'Phân loại mới' => $updates['direction'] ?? '(Giữ nguyên)',
                            'Cơ quan mới' => $updates['issuing_agency'] ?? '(Giữ nguyên)',
                        ];
                    }
                } else {
                    $stats['documents_unchanged']++;
                }
            } elseif (count($candidates) > 1) {
                $stats['ambiguous']++;
            } else {
                $stats['unmatched']++;
            }
        }

        $this->newLine();
        $this->table(
            ['Chỉ số', 'Số lượng'],
            [
                ['Tổng số dòng dữ liệu Excel', $stats['total_excel_rows']],
                ['Khớp chính xác với database', $stats['matched']],
                ['Số văn bản sẽ được bổ sung dữ liệu', $stats['documents_to_update']],
                ['Số văn bản giữ nguyên (đã đủ dữ liệu)', $stats['documents_unchanged']],
                ['Số dòng không khớp', $stats['unmatched']],
                ['Số dòng trùng lặp / mơ hồ', $stats['ambiguous']],
                ['-- Bổ sung Trích yếu (title)', $stats['fields_filled']['title']],
                ['-- Bổ sung Phân loại (direction)', $stats['fields_filled']['direction']],
                ['-- Bổ sung Cơ quan ban hành (issuing_agency)', $stats['fields_filled']['issuing_agency']],
                ['-- Bổ sung Ngày ban hành (issued_date)', $stats['fields_filled']['issued_date']],
                ['-- Bổ sung Số hiệu (document_code)', $stats['fields_filled']['document_code']],
            ]
        );

        if (! empty($samplePreview)) {
            $this->newLine();
            $this->info('Mẫu 10 văn bản tiêu biểu được bổ sung thông tin (Preview):');
            $this->table(
                ['ID', 'File PDF', 'Các trường bổ sung', 'Trích yếu mới', 'Phân loại mới', 'Cơ quan mới'],
                $samplePreview
            );
        }

        if (! $isCommit) {
            $this->newLine();
            $this->warn('ĐANG CHẠY Ở CHẾ ĐỘ XEM TRƯỚC (DRY-RUN). CHƯA CÓ DỮ LIỆU NÀO ĐƯỢC THAY ĐỔI.');
            $this->line("Để thực thi cập nhật thật, hãy chạy lại lệnh với cờ: --commit");

            return self::SUCCESS;
        }

        // BẮT ĐẦU CẬP NHẬT THẬT (COMMIT MODE)
        $this->newLine();
        $this->info('BẮT ĐẦU QUÁ TRÌNH CẬP NHẬT DỮ LIỆU CHÍNH THỨC...');

        if ($shouldBackup) {
            $backupTableName = 'documents_backup_' . date('Ymd_His');
            $this->info("Đang tạo bảng sao lưu dữ liệu: `{$backupTableName}`...");
            try {
                DB::statement("CREATE TABLE `{$backupTableName}` AS SELECT * FROM `documents`");
                $this->info("-> Sao lưu hoàn tất thành công!");
            } catch (Throwable $e) {
                $this->error("Không thể tạo bảng sao lưu: " . $e->getMessage());

                return self::FAILURE;
            }
        }

        $bar = $this->output->createProgressBar(count($pendingUpdates));
        $bar->start();

        $sourceFileBasename = basename($filePath);

        try {
            DB::transaction(function () use ($pendingUpdates, $sourceFileBasename, $user, $bar) {
                foreach ($pendingUpdates as $item) {
                    $doc = $item['document'];
                    $updates = $item['updates'];
                    $before = $item['before'];

                    $doc->update($updates);

                    DocumentLog::create([
                        'document_id' => $doc->id,
                        'user_id' => $user->id,
                        'action' => 'ocr_metadata_enriched',
                        'details' => json_encode([
                            'source' => $sourceFileBasename,
                            'excel_row' => $item['excel_row'],
                            'excel_stt' => $item['excel_stt'],
                            'file_name' => $item['file_name'],
                            'before' => $before,
                            'after' => $updates,
                        ], JSON_UNESCAPED_UNICODE),
                    ]);

                    $bar->advance();
                }
            });
        } catch (Throwable $e) {
            $this->newLine();
            $this->error("Lỗi trong quá trình cập nhật database (đã tự động rollback): " . $e->getMessage());

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("CẬP NHẬT HOÀN TẤT THÀNH CÔNG! Đã bổ sung dữ liệu cho " . count($pendingUpdates) . " văn bản.");
        $this->info("Toàn bộ nhật ký thay đổi đã được lưu vào bảng `document_logs` với action 'ocr_metadata_enriched'.");

        return self::SUCCESS;
    }
}
