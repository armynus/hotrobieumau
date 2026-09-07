<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Trích yếu trong sổ Excel có thể dài hơn 255 ký tự.
            $table->text('title')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('title', 255)->nullable()->change();
        });
    }
};
