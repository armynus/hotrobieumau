<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('content_group', 32)->default('transaction')->after('value');
            $table->unsignedInteger('display_order')->default(0)->after('content_group');
        });

        $positions = [];
        $inferGroup = static function (string $code): string {
            return match (true) {
                (bool) preg_match('/branch|DiaDanh|NgayThangNam|NgayGiaoDich|GDichVien|GiaoDichVien|KiemSoat|NguoiLap|NgayLap/i', $code) => 'preparation',
                (bool) preg_match('/identity|CCCD|CMND|HoChieu|MaSoThue|MST|DKKD|Giay|DaiDien|NguoiUQ|NgayUQ|ChucVu/i', $code) => 'identity',
                (bool) preg_match('/idxacno|TaiKhoan|TKTT|LoaiThe|HangThe|SoThe|Banking|DichVu|ThuTuDong|ccycd|TKTC/i', $code) => 'account',
                in_array($code, ['custno', 'name', 'nameloc', 'gender', 'birthday', 'phone_no', 'addrfull', 'addr1', 'addr2', 'addr3', 'QuocTich', 'NgheNghiepKH', 'MaKHDN', 'TenDoanhNghiep', 'SoDienThoai', 'DiaChiDoanhNghiep', 'custtpcd', 'custdtltpcd'], true) => 'customer',
                default => 'transaction',
            };
        };

        DB::table('form_fields')->select(['id', 'field_code'])->orderBy('id')->get()->each(function ($field) use (&$positions, $inferGroup): void {
            $group = $inferGroup((string) $field->field_code);
            $positions[$group] = ($positions[$group] ?? 0) + 10;
            DB::table('form_fields')->where('id', $field->id)->update([
                'content_group' => $group,
                'display_order' => $positions[$group],
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn(['content_group', 'display_order']);
        });
    }
};
