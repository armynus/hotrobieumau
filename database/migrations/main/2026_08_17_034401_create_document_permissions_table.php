<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();

            /*
             * Các loại target:
             *
             * branch
             * department
             * position
             * user
             */
            $table->string('target_type');

            $table->unsignedBigInteger('target_id');

            /*
             * view     = được xem
             * manage   = được quản lý
             */
            $table->string('permission')
                ->default('view');

            /*
             * Ai cấp quyền
             */
            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('granted_at')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'document_id',
                'target_type',
                'target_id',
                'permission'
            ], 'doc_perm_unique');

            $table->index([
                'target_type',
                'target_id'
            ]);

            $table->index([
                'document_id',
                'target_type'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_permissions');
    }
};
