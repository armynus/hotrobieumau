<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('it_support_requests', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->text('resolution_note')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('resolution_note');
            $table->index(['user_id', 'created_at'], 'it_support_user_created_idx');
            $table->index(['status', 'created_at'], 'it_support_status_created_idx');
            $table->index('completed_at', 'it_support_completed_idx');
        });

        Schema::create('it_support_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('it_support_requests')->cascadeOnDelete();
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['request_id', 'created_at']);
        });

        DB::table('it_support_requests')->whereIn('status', ['resolved', 'closed'])
            ->whereNull('completed_at')->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('it_support_events');
        Schema::table('it_support_requests', function (Blueprint $table) {
            $table->dropIndex('it_support_user_created_idx');
            $table->dropIndex('it_support_status_created_idx');
            $table->dropIndex('it_support_completed_idx');
            $table->dropColumn(['description', 'resolution_note', 'completed_at']);
        });
    }
};
