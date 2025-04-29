<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_forms', function (Blueprint $table) {
            $table->unsignedBigInteger('sup_form_type_id')->nullable()->after('form_type');
            $table->foreign('sup_form_type_id')
                ->references('id')->on('sup_form_type')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('support_forms', function (Blueprint $table) {
            $table->dropForeign(['sup_form_type_id']);
            $table->dropColumn('sup_form_type_id');
        });
    }
};
