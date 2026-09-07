<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('read_at')
                ->nullable();

            $table->timestamps();

            /*
             * Một user - một văn bản chỉ có một trạng thái đọc.
             */
            $table->unique([
                'document_id',
                'user_id'
            ]);

            $table->index([
                'user_id',
                'read_at'
            ]);

            $table->index([
                'document_id',
                'read_at'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_reads');
    }
};
