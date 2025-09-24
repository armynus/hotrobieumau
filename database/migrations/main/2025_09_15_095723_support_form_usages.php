<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('support_form_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('support_form_id');
            $table->timestamp('used_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'support_form_id']); // ensure one row per user+form
            $table->index('used_at');

            // nếu muốn FK (optional)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('support_form_id')->references('id')->on('support_forms')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_form_usages');
    }
};
