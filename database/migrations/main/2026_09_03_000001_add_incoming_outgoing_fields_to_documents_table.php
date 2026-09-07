<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('direction', 20)->default('incoming')->index();
            $table->string('registry_number')->nullable()->index();
            $table->date('forwarded_date')->nullable()->index();
            $table->text('recipient')->nullable();
            $table->text('archive_recipient')->nullable();
            $table->unsignedInteger('copy_count')->nullable();
            $table->string('receipt_signature')->nullable();
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['direction']);
            $table->dropIndex(['registry_number']);
            $table->dropIndex(['forwarded_date']);
            $table->dropColumn([
                'direction',
                'registry_number',
                'forwarded_date',
                'recipient',
                'archive_recipient',
                'copy_count',
                'receipt_signature',
                'notes',
            ]);
        });
    }
};
