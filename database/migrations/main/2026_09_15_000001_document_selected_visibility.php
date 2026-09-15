<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Chỉ chuyển tên mức cũ; không tự tạo người/phòng ban nhận.
        DB::table('documents')->where('visibility', 'private')->update(['visibility' => 'normal']);
        DB::table('documents')->where('visibility', 'restricted')->update(['visibility' => 'private']);
        DB::table('documents')->whereIn('visibility', ['branch', 'system'])->update(['visibility' => 'public']);
    }

    public function down(): void
    {
        throw new RuntimeException('Không tự phục hồi quyền xem rộng của phiên bản cũ. Khôi phục backup nếu cần.');
    }
};
