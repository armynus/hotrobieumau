<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_attachments', function (Blueprint $table) {
            // Cấu trúc NAM/THANG/NGAY dài hơn đường dẫn số cũ.
            $table->string('file_path', 1024)->change();
        });
    }

    public function down(): void
    {
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->string('file_path', 255)->change();
        });
    }
};
