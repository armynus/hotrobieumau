<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
        });
        Schema::table('document_ledger_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable()->change();
            $table->text('title')->nullable()->comment('Trích yếu riêng của sổ, không đồng bộ kho');
            $table->date('issued_date')->nullable();
            $table->date('forwarded_date')->nullable();
            $table->string('issuing_agency')->nullable();
            $table->string('signer')->nullable();
            $table->text('recipient')->nullable();
            $table->text('archive_recipient')->nullable();
            $table->unsignedInteger('copy_count')->nullable();
            $table->string('receipt_signature')->nullable();
            $table->text('notes')->nullable();
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
        });
        // Chụp lại dữ liệu sổ cũ trước khi tách. Không xóa/sửa văn bản hoặc file hiện có.
        $fields = ['title', 'issued_date', 'forwarded_date', 'issuing_agency', 'signer', 'recipient', 'archive_recipient', 'copy_count', 'receipt_signature', 'notes'];
        DB::table('document_ledger_entries')->orderBy('id')->chunkById(500, function ($entries) use ($fields) {
            $documents = DB::table('documents')->whereIn('id', $entries->pluck('document_id')->filter())->get(array_merge(['id'], $fields))->keyBy('id');
            foreach ($entries as $entry) {
                if ($document = $documents->get($entry->document_id)) {
                    $data = array_intersect_key((array) $document, array_flip($fields));
                    if ($entry->book !== 'incoming') {
                        $data['forwarded_date'] = $entry->registered_date;
                    }
                    DB::table('document_ledger_entries')->where('id', $entry->id)->update($data);
                }
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Sổ đã có dữ liệu độc lập. Không rollback tự động vì sẽ làm mất dữ liệu sổ; khôi phục bản sao lưu nếu cần.');
    }
};
