<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();

            /*
             * Từ chi nhánh nào
             */
            $table->unsignedBigInteger('from_branch_id')
                ->nullable();

            /*
             * Tới mục tiêu nào
             */
            $table->unsignedBigInteger('to_branch_id')
                ->nullable();

            $table->unsignedBigInteger('to_department_id')
                ->nullable();

            $table->unsignedBigInteger('to_user_id')
                ->nullable();

            /*
             * Người thực hiện chuyển
             */
            $table->foreignId('transferred_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('transferred_at')
                ->useCurrent();

            $table->text('note')
                ->nullable();

            /*
             * pending
             * received
             * rejected
             */
            $table->string('status')
                ->default('pending');

            $table->timestamps();

            $table->index([
                'document_id',
                'transferred_at'
            ]);

            $table->index('from_branch_id');
            $table->index('to_branch_id');
            $table->index('to_department_id');
            $table->index('to_user_id');

            $table->foreign('from_branch_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();

            $table->foreign('to_branch_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();

            $table->foreign('to_department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->foreign('to_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_transfers');
    }
};
