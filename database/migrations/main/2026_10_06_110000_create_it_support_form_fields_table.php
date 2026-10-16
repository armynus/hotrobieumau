<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('it_support_form_fields', function (Blueprint $table) {
            $table->id();
            // Mã trường (dùng trong file docx dạng ${field_code})
            $table->string('field_code')->unique();
            // Tên trường hiển thị tiếng Việt
            $table->string('field_name');
            // Kiểu dữ liệu: text, textarea, date, number, select...
            $table->string('data_type', 50)->default('text');
            // Gợi ý nhập liệu
            $table->string('placeholder')->nullable();
            // Giá trị mặc định
            $table->string('value')->nullable();
            // Nhóm nội dung: requester, system_device, issue_request, approval
            $table->string('content_group', 32)->default('requester');
            // Thứ tự hiển thị
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        // Nạp dữ liệu trường mẫu chuẩn ban đầu cho Biểu mẫu IT
        $initialFields = [
            // 1. Nhóm Thông tin cán bộ & Đơn vị (requester)
            [
                'field_code'    => 'TenCanBo',
                'field_name'    => 'Họ và tên cán bộ yêu cầu',
                'data_type'     => 'text',
                'placeholder'   => 'Nhập họ tên cán bộ',
                'value'         => null,
                'content_group' => 'requester',
                'display_order' => 10,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'SoDienThoai',
                'field_name'    => 'Số điện thoại liên hệ',
                'data_type'     => 'text',
                'placeholder'   => 'Nhập số điện thoại',
                'value'         => null,
                'content_group' => 'requester',
                'display_order' => 20,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'ChucVu',
                'field_name'    => 'Chức vụ',
                'data_type'     => 'text',
                'placeholder'   => 'Nhập chức vụ',
                'value'         => null,
                'content_group' => 'requester',
                'display_order' => 30,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'MaChiNhanh',
                'field_name'    => 'Mã chi nhánh',
                'data_type'     => 'text',
                'placeholder'   => 'Mã chi nhánh/đơn vị',
                'value'         => null,
                'content_group' => 'requester',
                'display_order' => 40,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'TenChiNhanh',
                'field_name'    => 'Tên chi nhánh / Đơn vị',
                'data_type'     => 'text',
                'placeholder'   => 'Tên chi nhánh/đơn vị',
                'value'         => null,
                'content_group' => 'requester',
                'display_order' => 50,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'PhongBan',
                'field_name'    => 'Phòng ban / Bộ phận',
                'data_type'     => 'text',
                'placeholder'   => 'Nhập phòng ban hoặc bộ phận',
                'value'         => null,
                'content_group' => 'requester',
                'display_order' => 60,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],

            // 2. Nhóm Thiết bị & Hệ thống (system_device)
            [
                'field_code'    => 'TenThietBi',
                'field_name'    => 'Tên thiết bị / Phần mềm',
                'data_type'     => 'text',
                'placeholder'   => 'Ví dụ: Máy in HP, IPCAS, Outlook...',
                'value'         => null,
                'content_group' => 'system_device',
                'display_order' => 10,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'DiaChiIP',
                'field_name'    => 'Địa chỉ IP máy tính',
                'data_type'     => 'text',
                'placeholder'   => 'Ví dụ: 10.x.x.x',
                'value'         => null,
                'content_group' => 'system_device',
                'display_order' => 20,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'MaTaiSan',
                'field_name'    => 'Mã tài sản / Số máy',
                'data_type'     => 'text',
                'placeholder'   => 'Mã định danh thiết bị',
                'value'         => null,
                'content_group' => 'system_device',
                'display_order' => 30,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'TenHeThong',
                'field_name'    => 'Tên hệ thống thông tin',
                'data_type'     => 'text',
                'placeholder'   => 'IPCAS, Agribank Plus, AD...',
                'value'         => null,
                'content_group' => 'system_device',
                'display_order' => 40,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],

            // 3. Nhóm Nội dung sự cố & Đề nghị (issue_request)
            [
                'field_code'    => 'TieuDeYeuCau',
                'field_name'    => 'Tiêu đề yêu cầu / Sự cố',
                'data_type'     => 'text',
                'placeholder'   => 'Tóm tắt nội dung yêu cầu',
                'value'         => null,
                'content_group' => 'issue_request',
                'display_order' => 10,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'NoiDungYeuCau',
                'field_name'    => 'Nội dung chi tiết yêu cầu',
                'data_type'     => 'textarea',
                'placeholder'   => 'Mô tả chi tiết nội dung sự cố hoặc yêu cầu cần hỗ trợ',
                'value'         => null,
                'content_group' => 'issue_request',
                'display_order' => 20,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'LyDo',
                'field_name'    => 'Lý do đề nghị',
                'data_type'     => 'textarea',
                'placeholder'   => 'Lý do thực hiện đề nghị',
                'value'         => null,
                'content_group' => 'issue_request',
                'display_order' => 30,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'NgayYeuCau',
                'field_name'    => 'Ngày yêu cầu',
                'data_type'     => 'date',
                'placeholder'   => 'dd/mm/yyyy',
                'value'         => null,
                'content_group' => 'issue_request',
                'display_order' => 40,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],

            // 4. Nhóm Phê duyệt & Tiếp nhận (approval)
            [
                'field_code'    => 'CanBoIT',
                'field_name'    => 'Cán bộ IT tiếp nhận',
                'data_type'     => 'text',
                'placeholder'   => 'Họ tên cán bộ IT phụ trách',
                'value'         => null,
                'content_group' => 'approval',
                'display_order' => 10,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'KetQuaXuLy',
                'field_name'    => 'Kết quả xử lý',
                'data_type'     => 'textarea',
                'placeholder'   => 'Nội dung kết quả xử lý của cán bộ IT',
                'value'         => null,
                'content_group' => 'approval',
                'display_order' => 20,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'field_code'    => 'LanhDaoPheDuyet',
                'field_name'    => 'Lãnh đạo đơn vị phê duyệt',
                'data_type'     => 'text',
                'placeholder'   => 'Họ tên lãnh đạo phê duyệt',
                'value'         => null,
                'content_group' => 'approval',
                'display_order' => 30,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        DB::table('it_support_form_fields')->insert($initialFields);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_support_form_fields');
    }
};
