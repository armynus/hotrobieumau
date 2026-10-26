<?php

use App\Models\ItSupportFormField;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Khôi phục các trường viết HOA vào từ điển trường IT
        ItSupportFormField::firstOrCreate(
            ['field_code' => 'ChiNhanhHOA'],
            [
                'field_name'    => 'Tên chi nhánh (VIẾT HOA)',
                'data_type'     => 'uppercase',
                'placeholder'   => 'Tên chi nhánh viết hoa',
                'value'         => null,
                'content_group' => 'requester',
                'display_order' => 52,
            ]
        );

        ItSupportFormField::firstOrCreate(
            ['field_code' => 'TenCanBoHOA'],
            [
                'field_name'    => 'Họ và tên cán bộ (VIẾT HOA)',
                'data_type'     => 'uppercase',
                'placeholder'   => 'Họ tên cán bộ viết hoa',
                'value'         => null,
                'content_group' => 'requester',
                'display_order' => 12,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ItSupportFormField::whereIn('field_code', ['ChiNhanhHOA', 'TenCanBoHOA'])->delete();
    }
};
