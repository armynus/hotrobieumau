<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $collation = Schema::getConnection()->getDriverName() === 'sqlite' ? 'BINARY' : 'utf8mb4_bin';
        Schema::create('geography_imports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sha256', 64);
            $table->date('snapshot_date');
            $table->string('source_path');
            $table->json('notes');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at');
        });
        Schema::create('geography_records', function (Blueprint $table) use ($collation) {
            $table->id();
            $table->foreignId('import_id')->constrained('geography_imports');
            $table->string('old_key', 64);
            $table->string('old_province')->collation($collation);
            $table->string('old_district')->collation($collation);
            $table->string('old_name')->nullable();
            $table->string('new_code', 5);
            $table->string('new_name');
            $table->string('new_province')->collation($collation);
            $table->string('scope', 30)->nullable();
            $table->string('source_relation')->nullable();
            $table->boolean('verified')->default(false);
            $table->boolean('enabled')->default(true);
            $table->text('note')->nullable();
            $table->string('evidence', 2000)->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->text('search_text');
            $table->unsignedInteger('revision')->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->unique(['import_id', 'old_key', 'new_code']);
            $table->index(['import_id', 'enabled', 'old_key']);
            $table->index(['import_id', 'enabled', 'new_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geography_records');
        Schema::dropIfExists('geography_imports');
    }
};
