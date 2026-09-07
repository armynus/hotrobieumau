<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {

            $table->id();

            /*
             * Thông tin văn bản
             */
            $table->string('document_number')->nullable();
            $table->string('document_code')->nullable();

            $table->string('title');
            $table->text('summary')->nullable();

            $table->foreignId('document_type_id')
                ->nullable()
                ->constrained('document_types')
                ->nullOnDelete();

            /*
             * Chi nhánh quản lý/sở hữu
             */
            $table->unsignedBigInteger('managing_branch_id')
                ->nullable();

            /*
             * Nguồn phát hành
             *
             * TW => source_branch_id có thể là branch_type = central
             */
            $table->unsignedBigInteger('source_branch_id')
                ->nullable();

            /*
             * Thông tin ngày
             */
            $table->date('issued_date')->nullable();
            $table->date('received_date')->nullable();

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            /*
             * Ban hành
             */
            $table->string('issuing_agency')->nullable();
            $table->string('signer')->nullable();

            /*
             * Mức độ
             */
            $table->string('priority')
                ->default('normal');

            $table->string('security_level')
                ->default('normal');

            /*
             * (File đính kèm đã được tách sang bảng document_attachments)
             */

            /*
             * Ai tiếp nhận / đăng tải
             */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Trạng thái
             */
            $table->string('status')
                ->default('active');

            $table->timestamps();
            $table->softDeletes();

            /*
             * Index
             */
            $table->index('document_number');
            $table->index('document_code');
            $table->index('document_type_id');
            $table->index('source_branch_id');
            $table->index('issued_date');
            $table->index('received_date');
            $table->index('priority');
            $table->index('security_level');

            $table->index([
                'status',
                'issued_date',
            ]);

            $table->index('managing_branch_id');

            $table->foreign('source_branch_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();

            $table->foreign('managing_branch_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};