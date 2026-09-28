<?php

namespace App\Console\Commands;

use App\Imports\OcrResultsImport;
use App\Models\Document;
use App\Models\DocumentLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportOcrFromExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ocr:import-excel {file=ocr_results.xlsx : Tên file Excel trong thư mục storage/app}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cập nhật lại dữ liệu văn bản từ file Excel kết quả OCR';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileName = $this->argument('file');

        if (! Storage::disk('local')->exists($fileName)) {
            $this->error("Không tìm thấy file $fileName trong thư mục storage/app (local disk).");
            return self::FAILURE;
        }

        $this->info("Đang đọc file Excel $fileName...");

        try {
            $import = new OcrResultsImport();
            Excel::import($import, $fileName, 'local');
            $rows = $import->data;
        } catch (Throwable $e) {
            $this->error("Lỗi khi đọc file Excel: " . $e->getMessage());
            return self::FAILURE;
        }

        if ($rows->isEmpty()) {
            $this->info("File Excel không có dữ liệu.");
            return self::SUCCESS;
        }

        $this->info("Bắt đầu cập nhật {$rows->count()} dòng dữ liệu...");
        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        $updatedCount = 0;

        foreach ($rows as $row) {
            // Laravel Excel tự động chuyển headings thành lowercase, snake_case
            // Ví dụ: 'Document ID' -> 'document_id', 'Ngày ban hành (OCR)' -> 'ngay_ban_hanh_ocr'
            // Tuy nhiên tuỳ thuộc vào phiên bản/config của Maatwebsite Excel, 
            // có thể heading key sẽ giữ nguyên hoặc chuyển đổi.
            // Để an toàn, chúng ta lấy mảng row để kiểm tra key.
            
            $rowArray = $row->toArray();
            
            // Tìm key ID
            $idKey = $this->findKey($rowArray, ['document id', 'document_id', 'id']);
            if (!$idKey || empty($rowArray[$idKey])) {
                $bar->advance();
                continue;
            }

            $documentId = $rowArray[$idKey];
            
            $dateKey = $this->findKey($rowArray, ['ngày ban hành (ocr)', 'ngay_ban_hanh_ocr']);
            $titleKey = $this->findKey($rowArray, ['trích yếu (ocr)', 'trich_yeu_ocr']);

            $newDate = $dateKey ? $rowArray[$dateKey] : null;
            $newTitle = $titleKey ? $rowArray[$titleKey] : null;

            $document = Document::find($documentId);
            if (!$document) {
                $bar->advance();
                continue;
            }

            $updates = [];
            if (!empty($newDate) && blank($document->issued_date)) {
                $updates['issued_date'] = trim($newDate);
            }
            if (!empty($newTitle) && blank($document->title)) {
                $updates['title'] = trim($newTitle);
            }

            if (!empty($updates)) {
                DB::transaction(function () use ($document, $updates) {
                    $document->update($updates);
                    
                    DocumentLog::create([
                        'document_id' => $document->id,
                        'user_id' => null, // Hệ thống cập nhật
                        'action' => 'metadata_updated_from_excel',
                        'details' => [
                            'updated_fields' => array_keys($updates),
                            'source' => 'excel_import'
                        ],
                    ]);
                });
                $updatedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("Đã cập nhật thành công $updatedCount văn bản.");

        return self::SUCCESS;
    }

    private function findKey(array $row, array $possibleKeys): ?string
    {
        foreach ($row as $key => $value) {
            $normalizedKey = mb_strtolower((string)$key, 'UTF-8');
            if (in_array($normalizedKey, $possibleKeys)) {
                return $key;
            }
        }
        return null;
    }
}
