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
        // 1. Roles table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 50)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->string('status', 20)->default('active');
                $table->timestamps();
            });
        }

        // 2. Permissions table
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('slug', 100)->unique();
                $table->string('module', 50)->index();
                $table->string('module_name', 100);
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 3. Role - Permission pivot table
        if (!Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
                $table->primary(['role_id', 'permission_id']);
            });
        }

        // 4. User - Role pivot table
        if (!Schema::hasTable('user_role')) {
            Schema::create('user_role', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->primary(['user_id', 'role_id']);
            });
        }

        // Seed initial permissions & roles
        $this->seedInitialRbacData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }

    private function seedInitialRbacData(): void
    {
        $now = now();

        // Danh sách quyền chuẩn theo 5 phân hệ nghiệp vụ
        $permissions = [
            // Phân hệ 1: Quản lý Văn bản
            [
                'name' => 'Xem kho văn bản & văn bản đến',
                'slug' => 'documents.view',
                'module' => 'documents',
                'module_name' => 'Quản lý Văn bản',
                'description' => 'Xem danh sách văn bản đến tôi và tra cứu kho văn bản theo quyền phân phối',
                'sort_order' => 1,
            ],
            [
                'name' => 'Đăng tải văn bản mới',
                'slug' => 'documents.upload',
                'module' => 'documents',
                'module_name' => 'Quản lý Văn bản',
                'description' => 'Đăng tải văn bản đến, văn bản đi và quyết định lưu kho',
                'sort_order' => 2,
            ],
            [
                'name' => 'Quản lý & ghi sổ văn bản',
                'slug' => 'documents.manage_ledger',
                'module' => 'documents',
                'module_name' => 'Quản lý Văn bản',
                'description' => 'Quản lý số văn bản, ghi sổ văn bản đến/đi và xuất phiếu trình',
                'sort_order' => 3,
            ],
            [
                'name' => 'Phân phối & chuyển tiếp văn bản',
                'slug' => 'documents.distribute',
                'module' => 'documents',
                'module_name' => 'Quản lý Văn bản',
                'description' => 'Chuyển tiếp và phân phối văn bản cho phòng ban, chi nhánh và cá nhân',
                'sort_order' => 4,
            ],
            [
                'name' => 'Xem báo cáo văn bản',
                'slug' => 'documents.reports',
                'module' => 'documents',
                'module_name' => 'Quản lý Văn bản',
                'description' => 'Xem thống kê, báo cáo tổng hợp văn bản đến, đi và quyết định',
                'sort_order' => 5,
            ],

            // Phân hệ 2: Biểu mẫu nghiệp vụ & In ấn
            [
                'name' => 'Xem biểu mẫu giao dịch',
                'slug' => 'forms.view',
                'module' => 'forms',
                'module_name' => 'Biểu mẫu nghiệp vụ',
                'description' => 'Tra cứu và xem danh mục biểu mẫu giao dịch nghiệp vụ ngân hàng',
                'sort_order' => 10,
            ],
            [
                'name' => 'Điền và in ấn biểu mẫu',
                'slug' => 'forms.print',
                'module' => 'forms',
                'module_name' => 'Biểu mẫu nghiệp vụ',
                'description' => 'Nhập dữ liệu khách hàng/tài khoản và xuất in file Word mẫu biểu',
                'sort_order' => 11,
            ],
            [
                'name' => 'Sử dụng bộ hồ sơ biểu mẫu',
                'slug' => 'forms.bundle',
                'module' => 'forms',
                'module_name' => 'Biểu mẫu nghiệp vụ',
                'description' => 'Tạo gói và tải xuống nhiều biểu mẫu cùng lúc theo bộ nghiệp vụ',
                'sort_order' => 12,
            ],
            [
                'name' => 'Quản lý danh mục & file mẫu (Admin)',
                'slug' => 'forms.manage',
                'module' => 'forms',
                'module_name' => 'Biểu mẫu nghiệp vụ',
                'description' => 'Thêm, sửa, xóa file Word template, cấu hình trường dữ liệu và phân loại mẫu',
                'sort_order' => 13,
            ],

            // Phân hệ 3: Hỗ trợ IT
            [
                'name' => 'Gửi yêu cầu hỗ trợ IT',
                'slug' => 'it_support.request',
                'module' => 'it_support',
                'module_name' => 'Hỗ trợ IT',
                'description' => 'Tạo phiếu yêu cầu hỗ trợ kỹ thuật, Core Banking IPCAS, mạng, máy in, phần mềm',
                'sort_order' => 20,
            ],
            [
                'name' => 'Xem & in biểu mẫu IT',
                'slug' => 'it_support.templates',
                'module' => 'it_support',
                'module_name' => 'Hỗ trợ IT',
                'description' => 'Tra cứu và điền thông tin in các mẫu biểu văn bản đề xuất kỹ thuật IT',
                'sort_order' => 21,
            ],
            [
                'name' => 'Tiếp nhận & xử lý yêu cầu IT',
                'slug' => 'it_support.manage',
                'module' => 'it_support',
                'module_name' => 'Hỗ trợ IT',
                'description' => 'Tiếp nhận, phản hồi, xử lý sự cố và cập nhật trạng thái phiếu hỗ trợ IT',
                'sort_order' => 22,
            ],
            [
                'name' => 'Quản lý biểu mẫu & danh mục IT',
                'slug' => 'it_support.categories_manage',
                'module' => 'it_support',
                'module_name' => 'Hỗ trợ IT',
                'description' => 'Cấu hình danh mục sự cố, upload template Word IT và quản lý trường biểu mẫu IT',
                'sort_order' => 23,
            ],

            // Phân hệ 4: Dữ liệu Tổng hợp & Tra cứu
            [
                'name' => 'Tra cứu dữ liệu khách hàng',
                'slug' => 'data.customers.view',
                'module' => 'data',
                'module_name' => 'Dữ liệu & Tra cứu',
                'description' => 'Tìm kiếm thông tin khách hàng cá nhân/doanh nghiệp đã đồng bộ',
                'sort_order' => 30,
            ],
            [
                'name' => 'Upload nhập file dữ liệu khách hàng',
                'slug' => 'data.customers.upload',
                'module' => 'data',
                'module_name' => 'Dữ liệu & Tra cứu',
                'description' => 'Tải lên danh sách khách hàng từ file Excel vào cơ sở dữ liệu chi nhánh',
                'sort_order' => 31,
            ],
            [
                'name' => 'Tra cứu dữ liệu tài khoản',
                'slug' => 'data.accounts.view',
                'module' => 'data',
                'module_name' => 'Dữ liệu & Tra cứu',
                'description' => 'Tra cứu số tài khoản, số dư và thông tin liên kết tài khoản ngân hàng',
                'sort_order' => 32,
            ],
            [
                'name' => 'Upload nhập file dữ liệu tài khoản',
                'slug' => 'data.accounts.upload',
                'module' => 'data',
                'module_name' => 'Dữ liệu & Tra cứu',
                'description' => 'Tải lên danh sách tài khoản từ file Excel vào cơ sở dữ liệu chi nhánh',
                'sort_order' => 33,
            ],
            [
                'name' => 'Tra cứu sáp nhập địa giới hành chính',
                'slug' => 'data.merger_lookup',
                'module' => 'data',
                'module_name' => 'Dữ liệu & Tra cứu',
                'description' => 'Tra cứu chuyển đổi địa danh cũ/mới xã, phường, huyện, tỉnh trên toàn quốc',
                'sort_order' => 34,
            ],
            [
                'name' => 'Sử dụng Camera Scan QR CCCD',
                'slug' => 'data.scan_qr',
                'module' => 'data',
                'module_name' => 'Dữ liệu & Tra cứu',
                'description' => 'Quét mã QR trên Căn cước công dân gắn chip để trích xuất tự động thông tin',
                'sort_order' => 35,
            ],

            // Phân hệ 5: Quản trị Hệ thống
            [
                'name' => 'Xem danh sách nhân viên',
                'slug' => 'system.users.view',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Xem danh sách tài khoản nhân viên các chi nhánh, phòng ban, phòng giao dịch',
                'sort_order' => 40,
            ],
            [
                'name' => 'Quản lý tài khoản nhân viên',
                'slug' => 'system.users.manage',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Thêm mới, sửa thông tin hồ sơ, khóa và mở khóa tài khoản nhân viên',
                'sort_order' => 41,
            ],
            [
                'name' => 'Quản lý danh mục Chi nhánh',
                'slug' => 'system.branches.manage',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Cấu hình chi nhánh Loại I, Loại II, địa chỉ, số điện thoại, fax và đơn vị',
                'sort_order' => 42,
            ],
            [
                'name' => 'Quản lý danh mục Phòng ban',
                'slug' => 'system.departments.manage',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Thêm, sửa, cấu hình cơ cấu phòng ban trực thuộc từng chi nhánh',
                'sort_order' => 43,
            ],
            [
                'name' => 'Quản lý danh mục Phòng giao dịch',
                'slug' => 'system.transaction_offices.manage',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Quản lý phòng giao dịch trực thuộc chi nhánh, thông tin liên hệ và trạng thái',
                'sort_order' => 44,
            ],
            [
                'name' => 'Quản lý danh mục Chức vụ',
                'slug' => 'system.positions.manage',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Quản lý danh sách chức vụ, mã chức vụ và cấp bậc trong tổ chức',
                'sort_order' => 45,
            ],
            [
                'name' => 'Quản trị Vai trò & Ma trận Phân quyền (RBAC)',
                'slug' => 'system.roles.manage',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Thiết lập danh mục vai trò, phân quyền theo ma trận và gán vai trò nhân viên',
                'sort_order' => 46,
            ],
        ];

        foreach ($permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $p['slug']],
                array_merge($p, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // Danh sách Vai trò chuẩn của hệ thống
        $roles = [
            [
                'name' => 'Quản trị viên Hệ thống',
                'slug' => 'admin',
                'description' => 'Toàn quyền cấu hình, vận hành và quản trị trên toàn bộ hệ thống',
                'is_system' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Kiểm soát viên',
                'slug' => 'supervisor',
                'description' => 'Kiểm soát viên giao dịch, có quyền upload dữ liệu, scan QR và tra cứu mở rộng',
                'is_system' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Nhân viên nghiệp vụ',
                'slug' => 'staff',
                'description' => 'Cán bộ nhân viên nghiệp vụ ngân hàng: tra cứu khách hàng/tài khoản, điền và in biểu mẫu',
                'is_system' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Văn thư chi nhánh',
                'slug' => 'clerk',
                'description' => 'Phụ trách văn thư: đăng tải văn bản đến/đi, quản lý sổ văn bản và phân phối văn bản',
                'is_system' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Quản trị IT',
                'slug' => 'it_admin',
                'description' => 'Cán bộ phụ trách tin học: tiếp nhận, giải quyết hỗ trợ kỹ thuật và quản lý mẫu biểu IT',
                'is_system' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Lãnh đạo đơn vị',
                'slug' => 'leader',
                'description' => 'Ban Giám đốc, Lãnh đạo phòng ban: xem văn bản theo thẩm quyền và duyệt báo cáo',
                'is_system' => false,
                'status' => 'active',
            ],
        ];

        foreach ($roles as $r) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $r['slug']],
                array_merge($r, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // Lấy danh sách ID
        $permMap = DB::table('permissions')->pluck('id', 'slug')->toArray();
        $roleMap = DB::table('roles')->pluck('id', 'slug')->toArray();

        // Ma trận quyền hạn cho từng vai trò
        $rolePermMatrix = [
            // Admin: Có tất cả các quyền
            'admin' => array_keys($permMap),

            // Supervisor:
            'supervisor' => [
                'documents.view', 'documents.reports',
                'forms.view', 'forms.print', 'forms.bundle',
                'it_support.request', 'it_support.templates',
                'data.customers.view', 'data.customers.upload',
                'data.accounts.view', 'data.accounts.upload',
                'data.merger_lookup', 'data.scan_qr',
            ],

            // Staff:
            'staff' => [
                'documents.view',
                'forms.view', 'forms.print', 'forms.bundle',
                'it_support.request', 'it_support.templates',
                'data.customers.view', 'data.accounts.view',
                'data.merger_lookup',
            ],

            // Clerk:
            'clerk' => [
                'documents.view', 'documents.upload', 'documents.manage_ledger', 'documents.distribute', 'documents.reports',
                'forms.view', 'forms.print',
                'it_support.request', 'it_support.templates',
                'data.merger_lookup',
            ],

            // IT Admin:
            'it_admin' => [
                'it_support.request', 'it_support.templates', 'it_support.manage', 'it_support.categories_manage',
                'forms.view', 'forms.print',
                'data.merger_lookup',
                'system.users.view',
            ],

            // Leader:
            'leader' => [
                'documents.view', 'documents.reports',
                'forms.view', 'forms.print', 'forms.bundle',
                'it_support.request', 'it_support.templates',
                'data.customers.view', 'data.accounts.view', 'data.merger_lookup',
            ],
        ];

        foreach ($rolePermMatrix as $roleSlug => $permSlugs) {
            $roleId = $roleMap[$roleSlug] ?? null;
            if (!$roleId) continue;

            $permIds = [];
            foreach ($permSlugs as $slug) {
                if (isset($permMap[$slug])) {
                    $permIds[] = $permMap[$slug];
                }
            }

            foreach ($permIds as $permId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                ]);
            }
        }

        // Tự động đồng bộ người dùng hiện có sang bảng user_role
        $existingUsers = DB::table('users')->get(['id', 'role_id', 'document_role']);
        foreach ($existingUsers as $u) {
            $assignedRoleIds = [];

            if ($u->role_id == 0) {
                $assignedRoleIds[] = $roleMap['admin'] ?? null;
            } elseif ($u->role_id == 1) {
                $assignedRoleIds[] = $roleMap['supervisor'] ?? null;
            } else {
                $assignedRoleIds[] = $roleMap['staff'] ?? null;
            }

            if (($u->document_role ?? '') === 'clerk') {
                $assignedRoleIds[] = $roleMap['clerk'] ?? null;
            }

            foreach (array_filter(array_unique($assignedRoleIds)) as $rid) {
                DB::table('user_role')->updateOrInsert([
                    'user_id' => $u->id,
                    'role_id' => $rid,
                ]);
            }
        }
    }
};
