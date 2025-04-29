<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sup_form_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_type_id')->constrained('form_type')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sup_form_type');
    }
};
