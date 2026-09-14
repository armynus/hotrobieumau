<?php

use App\Support\DocumentCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_ledger_entries', function (Blueprint $table) {
            $table->dropUnique('ledger_unique_number');
            $table->index(['branch_id', 'year', 'book', 'number_key'], 'ledger_number_lookup');
            $table->string('code_key')->nullable()->comment('Số, ký hiệu chuẩn hóa để tra nhanh; không duy nhất');
            $table->char('source_fingerprint', 64)->nullable()->comment('Dấu vân tay nội dung dòng Excel để nhập lại không nhân bản');
            $table->index(['branch_id', 'year', 'book', 'code_key'], 'ledger_code_lookup');
        });
        DB::table('document_ledger_entries')->orderBy('id')->chunkById(500, function ($entries) {
            foreach ($entries as $entry) {
                DB::table('document_ledger_entries')->where('id', $entry->id)
                    ->update(['code_key' => DocumentCode::normalize($entry->document_code)]);
            }
        });
        // Quyết định cũ đã có sổ riêng: cập nhật đúng phân loại, giữ nguyên file/số sổ.
        DB::table('documents')->where('direction', 'outgoing')->whereIn('id', function ($query) {
            $query->select('document_id')->from('document_ledger_entries')->where('book', 'decision');
        })->update(['direction' => 'decision']);
    }

    public function down(): void
    {
        // Không thể khôi phục ràng buộc duy nhất khi sổ đã có số trùng.
        if (DB::table('document_ledger_entries')->select('branch_id', 'year', 'book', 'number_key')
            ->groupBy('branch_id', 'year', 'book', 'number_key')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Sổ có số trùng; cần xử lý thủ công trước khi rollback. Không có dữ liệu bị xóa.');
        }
        Schema::table('document_ledger_entries', function (Blueprint $table) {
            $table->unique(['branch_id', 'year', 'book', 'number_key'], 'ledger_unique_number');
            $table->dropIndex('ledger_number_lookup');
            $table->dropIndex('ledger_code_lookup');
            $table->dropColumn(['code_key', 'source_fingerprint']);
        });
        DB::table('documents')->where('direction', 'decision')->update(['direction' => 'outgoing']);
    }
};
