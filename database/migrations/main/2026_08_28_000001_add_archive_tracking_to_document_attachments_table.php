<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->string('archive_source_key', 64)->nullable()->unique('doc_attach_archive_source_unique');
            $table->text('archive_relative_path')->nullable();
            $table->string('checksum_sha256', 64)->nullable()->index('doc_attach_checksum_index');
        });
    }

    public function down(): void
    {
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->dropUnique('doc_attach_archive_source_unique');
            $table->dropIndex('doc_attach_checksum_index');
            $table->dropColumn(['archive_source_key', 'archive_relative_path', 'checksum_sha256']);
        });
    }
};
