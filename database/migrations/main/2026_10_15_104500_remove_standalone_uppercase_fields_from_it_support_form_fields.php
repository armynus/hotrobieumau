<?php

use App\Models\ItSupportFormField;
use App\Models\ItSupportTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Xóa các trường in hoa độc lập khỏi từ điển trường IT
        ItSupportFormField::whereIn('field_code', ['ChiNhanhHOA', 'TenCanBoHOA'])->delete();

        // 2. Chuẩn hóa lại danh sách fields của các template đã lưu
        $templates = ItSupportTemplate::all();
        foreach ($templates as $template) {
            $fields = is_array($template->fields) ? $template->fields : [];
            $hasTenCanBoHOA = in_array('TenCanBoHOA', $fields, true);
            $hasChiNhanhHOA = in_array('ChiNhanhHOA', $fields, true);

            if ($hasTenCanBoHOA || $hasChiNhanhHOA) {
                // Loại bỏ biến in hoa
                $fields = array_values(array_filter($fields, fn($f) => !in_array($f, ['TenCanBoHOA', 'ChiNhanhHOA'], true)));

                // Đảm bảo có trường gốc tương ứng
                if ($hasTenCanBoHOA && !in_array('TenCanBo', $fields, true)) {
                    $fields[] = 'TenCanBo';
                }
                if ($hasChiNhanhHOA && !in_array('TenChiNhanh', $fields, true)) {
                    $fields[] = 'TenChiNhanh';
                }

                $template->update(['fields' => array_values(array_unique($fields))]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không khôi phục vì các trường này tự động sinh trong code
    }
};
