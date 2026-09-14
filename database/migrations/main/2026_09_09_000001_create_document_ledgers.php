<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_ledger_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedSmallInteger('year');
            $table->string('book', 20)->comment('incoming: đến; outgoing: đi thường; decision: quyết định');
            $table->unsignedInteger('last_number')->default(0)->comment('Số lớn nhất từng cấp; không tái sử dụng sau khi xóa');
            $table->unique(['branch_id', 'year', 'book'], 'ledger_sequence_scope');
        });

        Schema::create('document_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->comment('Chi nhánh sở hữu sổ, không nhất thiết là nơi đăng văn bản');
            $table->unsignedSmallInteger('year')->comment('Năm vào sổ theo ngày đến/ngày chuyển');
            $table->string('book', 20)->comment('incoming, outgoing hoặc decision');
            $table->string('number', 50)->comment('Số sổ hiển thị, giữ 0 đầu và hậu tố nếu có');
            $table->string('number_key', 50)->comment('Khóa chống trùng đã chuẩn hóa: 01 và 1 là một số');
            $table->unsignedInteger('sequence_number')->comment('Phần số dùng sắp xếp và cấp số tiếp theo');
            $table->date('registered_date')->nullable()->comment('Ngày vào sổ của chi nhánh');
            $table->string('document_code')->nullable()->comment('Số, ký hiệu chính thức trong sổ, giữ dấu /');
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_sheet')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'year', 'book', 'number_key'], 'ledger_unique_number');
            $table->unique(['document_id', 'branch_id'], 'ledger_document_branch');
            $table->index(['branch_id', 'year', 'book', 'registered_date'], 'ledger_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ledger_entries');
        Schema::dropIfExists('document_ledger_sequences');
    }
};
