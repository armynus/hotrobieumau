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
        // 1. Tạo bảng danh mục cho Biểu mẫu IT
        Schema::create('it_support_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable()->default('fas fa-folder');
            $table->unsignedInteger('display_order')->default(10);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        // 2. Nạp dữ liệu 5 danh mục chuẩn ban đầu
        $now = now();
        $categories = [
            [
                'name'          => 'Hệ thống IPCAS',
                'description'   => 'Dành cho các yêu cầu, hỗ trợ hoặc báo lỗi liên quan đến hệ thống Core Banking (IPCAS), phân quyền giao dịch, mở/khóa user IPCAS...',
                'icon'          => 'fas fa-university',
                'display_order' => 10,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name'          => 'Các phần mềm nghiệp vụ',
                'description'   => 'Dành cho các phần mềm nghiệp vụ ngân hàng: Payment, Thanh toán song phương, E-Banking, Quản lý TSKT...',
                'icon'          => 'fas fa-cubes',
                'display_order' => 20,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name'          => 'Chứng thư số PKI',
                'description'   => 'Biểu mẫu đề nghị cấp mới, gia hạn, mở khóa mã PIN, thu hồi USB Token chứng thư số PKI và chữ ký số phục vụ giao dịch...',
                'icon'          => 'fas fa-key',
                'display_order' => 30,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name'          => 'Các chương trình BeAdmin',
                'description'   => 'Dành cho các chương trình quản trị hệ thống BeAdmin, quản trị người dùng máy tính Domain/Active Directory, phân quyền ứng dụng và phân hệ chi nhánh...',
                'icon'          => 'fas fa-user-cog',
                'display_order' => 40,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name'          => 'Yêu cầu hỗ trợ khác',
                'description'   => 'Các yêu cầu hỗ trợ kỹ thuật thiết bị tin học (máy tính, máy in), hạ tầng mạng LAN/WAN, email hoặc các vấn đề phát sinh khác...',
                'icon'          => 'fas fa-headset',
                'display_order' => 50,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        DB::table('it_support_categories')->insert($categories);

        // 3. Bổ sung trường category_id vào it_support_templates
        if (Schema::hasTable('it_support_templates') && !Schema::hasColumn('it_support_templates', 'category_id')) {
            Schema::table('it_support_templates', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('id')->constrained('it_support_categories')->nullOnDelete();
            });

            // Gán các template hiện tại vào danh mục đầu tiên nếu chưa có category_id
            $defaultCat = DB::table('it_support_categories')->where('display_order', 10)->first();
            if ($defaultCat) {
                DB::table('it_support_templates')->whereNull('category_id')->update(['category_id' => $defaultCat->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('it_support_templates') && Schema::hasColumn('it_support_templates', 'category_id')) {
            Schema::table('it_support_templates', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            });
        }

        Schema::dropIfExists('it_support_categories');
    }
};
