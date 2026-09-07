<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('branch_id');

            $table->string('department_name');
            $table->string('department_code')->nullable();

            $table->unsignedBigInteger('parent_id')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('department_code');

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->cascadeOnDelete();

            $table->foreign('parent_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};