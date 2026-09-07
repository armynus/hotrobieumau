<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_logs', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();
                
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('action'); // e.g. created, updated, deleted, restored, transferred
            $table->text('details')->nullable(); // JSON or text detailing the change

            $table->timestamps();
            
            $table->index('document_id');
            $table->index('user_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_logs');
    }
};
