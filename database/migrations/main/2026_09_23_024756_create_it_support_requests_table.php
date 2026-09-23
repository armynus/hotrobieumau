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
        Schema::create('it_support_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('pending'); // pending, processing, resolved, closed
            $table->json('attachment_paths')->nullable(); // array of paths
            $table->timestamps();

            // Foreign key (assuming users table exists)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_support_requests');
    }
};
