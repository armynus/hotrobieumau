<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();
                
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_extension')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            
            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_attachments');
    }
};
