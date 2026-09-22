<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_offices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('office_name');
            $table->string('office_code', 50)->nullable();
            $table->string('office_address')->nullable();
            $table->string('office_place')->nullable();
            $table->string('office_phone', 30)->nullable();
            $table->string('office_fax', 30)->nullable();
            $table->string('office_email')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['branch_id', 'office_name'], 'transaction_offices_branch_name_unique');
            $table->unique(['branch_id', 'office_code'], 'transaction_offices_branch_code_unique');
            $table->index(['branch_id', 'status'], 'transaction_offices_branch_status_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('transaction_office_id')
                ->nullable()
                ->after('department_id')
                ->constrained('transaction_offices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transaction_office_id');
        });

        Schema::dropIfExists('transaction_offices');
    }
};
