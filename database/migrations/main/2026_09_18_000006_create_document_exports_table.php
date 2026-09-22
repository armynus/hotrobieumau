<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->string('direction', 20);
            $table->string('period_type', 20);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->string('period_label');
            $table->string('file_name');
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('row_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'branch_id', 'status'], 'document_exports_active_idx');
            $table->index(['expires_at', 'status'], 'document_exports_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_exports');
    }
};
