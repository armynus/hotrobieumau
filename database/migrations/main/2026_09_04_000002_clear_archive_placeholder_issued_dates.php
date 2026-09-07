<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Importer cũ từng lấy ngày folder và ghi đồng thời vào ngày đến + ngày văn bản.
        // Ngày folder chỉ chứng minh ngày đến, vì vậy xóa giá trị issued_date giả để
        // sổ Excel hoặc OCR điền lại bằng ngày thực sự in trên văn bản.
        $archiveDocumentIds = DB::table('document_logs')
            ->select('document_id')
            ->where('action', 'archive_imported')
            ->distinct();

        DB::table('documents')
            ->whereIn('id', $archiveDocumentIds)
            ->whereColumn('issued_date', 'received_date')
            ->update(['issued_date' => null]);
    }

    public function down(): void
    {
        $archiveDocumentIds = DB::table('document_logs')
            ->select('document_id')
            ->where('action', 'archive_imported')
            ->distinct();

        DB::table('documents')
            ->whereIn('id', $archiveDocumentIds)
            ->whereNull('issued_date')
            ->whereNotNull('received_date')
            ->update(['issued_date' => DB::raw('received_date')]);
    }
};
