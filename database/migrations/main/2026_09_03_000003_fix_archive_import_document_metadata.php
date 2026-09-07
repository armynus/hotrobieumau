<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Import kho file cũ không thể tự suy ra trích yếu chính xác từ tên file.
            $table->string('title')->nullable()->change();
        });

        $archiveDocumentIds = DB::table('document_logs')
            ->select('document_id')
            ->where('action', 'archive_imported')
            ->distinct();

        DB::table('documents')
            ->whereIn('id', $archiveDocumentIds)
            ->orderBy('id')
            ->chunkById(500, function ($documents): void {
                $attachments = DB::table('document_attachments')
                    ->whereIn('document_id', $documents->pluck('id'))
                    ->whereNotNull('archive_source_key')
                    ->orderBy('id')
                    ->get()
                    ->unique('document_id')
                    ->keyBy('document_id');

                foreach ($documents as $document) {
                    $attachment = $attachments->get($document->id);
                    if (!$attachment) {
                        continue;
                    }

                    $sourceFileName = $attachment->archive_relative_path
                        ? basename(str_replace('\\', '/', $attachment->archive_relative_path))
                        : $attachment->file_name;
                    $documentCode = trim(pathinfo($sourceFileName, PATHINFO_FILENAME));

                    if ($documentCode === '') {
                        continue;
                    }

                    $updates = [];
                    if (trim((string) $document->document_code) === '') {
                        $updates['document_code'] = $documentCode;
                    }

                    // Chỉ xóa giá trị sai do importer cũ tạo ra. Trích yếu đã sửa tay được giữ nguyên.
                    if (trim((string) $document->title) === $documentCode) {
                        $updates['title'] = null;
                    }

                    if ($updates !== []) {
                        DB::table('documents')->where('id', $document->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('documents')
            ->whereNull('title')
            ->orderBy('id')
            ->chunkById(500, function ($documents): void {
                foreach ($documents as $document) {
                    DB::table('documents')->where('id', $document->id)->update([
                        'title' => $document->document_code ?: 'Chưa cập nhật trích yếu',
                    ]);
                }
            });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
        });
    }
};
