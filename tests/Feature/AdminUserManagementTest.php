<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminUserController;
use App\Models\Branches;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'admin_user_mgmt_test', 'database.connections.admin_user_mgmt_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('admin_user_mgmt_test');

        \Illuminate\Support\Facades\Schema::create('branches', function ($table) {
            $table->id();
            $table->string('branch_name');
            $table->string('branch_type')->default('type_2');
            $table->string('status')->default('active');
        });

        \Illuminate\Support\Facades\Schema::create('departments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('department_name');
            $table->string('department_code')->nullable();
            $table->string('status')->default('active');
        });

        \Illuminate\Support\Facades\Schema::create('positions', function ($table) {
            $table->id();
            $table->string('position_name');
            $table->string('position_code')->nullable();
            $table->unsignedInteger('level')->default(999);
            $table->string('status')->default('active');
        });

        \Illuminate\Support\Facades\Schema::create('transaction_offices', function ($table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('office_name');
            $table->string('office_code')->nullable();
            $table->string('status')->default('active');
        });

        \Illuminate\Support\Facades\Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('citizen_id', 20)->nullable();
            $table->string('employee_code', 50)->nullable();
            $table->string('email');
            $table->string('user_ipcas')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('transaction_office_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedTinyInteger('role_id');
            $table->string('document_role')->default('user');
            $table->string('status')->default('active');
            $table->string('phone_no', 50)->nullable();
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamps();
        });

        DB::table('branches')->insert([
            ['id' => 1, 'branch_name' => 'Chi nhánh Sa Đéc', 'branch_type' => 'type_2', 'status' => 'active'],
            ['id' => 2, 'branch_name' => 'Chi nhánh Cao Lãnh', 'branch_type' => 'type_1', 'status' => 'active'],
        ]);

        DB::table('departments')->insert([
            ['id' => 1, 'branch_id' => 1, 'department_name' => 'Phòng Kế toán', 'department_code' => 'KT', 'status' => 'active'],
            ['id' => 2, 'branch_id' => 2, 'department_name' => 'Phòng Tín dụng', 'department_code' => 'TD', 'status' => 'active'],
        ]);

        DB::table('positions')->insert([
            ['id' => 1, 'position_name' => 'Phó phòng', 'position_code' => 'PP', 'level' => 2, 'status' => 'active'],
            ['id' => 2, 'position_name' => 'Cán bộ nghiệp vụ', 'position_code' => 'CB', 'level' => 3, 'status' => 'active'],
        ]);

        \Illuminate\Support\Facades\Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('user_role', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Quản trị viên', 'slug' => 'admin', 'status' => 'active', 'is_system' => true],
            ['id' => 2, 'name' => 'Kiểm soát viên', 'slug' => 'supervisor', 'status' => 'active', 'is_system' => true],
            ['id' => 3, 'name' => 'Nhân viên', 'slug' => 'staff', 'status' => 'active', 'is_system' => true],
            ['id' => 4, 'name' => 'Văn thư', 'slug' => 'clerk', 'status' => 'active', 'is_system' => true],
        ]);
    }

    public function test_admin_can_store_user_with_citizen_id_and_employee_code(): void
    {
        $controller = app(AdminUserController::class);
        $request = Request::create('/admin_user_store', 'POST', [
            'name' => 'Nguyễn Văn Test',
            'citizen_id' => '087095001122',
            'employee_code' => 'NV-6500-001',
            'email' => 'nvtest@example.com',
            'user_ipcas' => 'NVT001',
            'password' => 'secret123',
            'branch_id' => 1,
            'department_id' => 1,
            'position_id' => 1,
            'role_id' => 2,
            'document_role' => 'user',
            'phone_no' => '0901234567',
        ]);

        $response = $controller->store($request);
        $this->assertTrue($response->getData()->status);

        $savedUser = User::where('email', 'nvtest@example.com')->first();
        $this->assertNotNull($savedUser);
        $this->assertSame('087095001122', $savedUser->citizen_id);
        $this->assertSame('NV-6500-001', $savedUser->employee_code);
        $this->assertSame('NVT001', $savedUser->user_ipcas);
        $this->assertSame('0901234567', $savedUser->phone_no);
        $this->assertSame('active', $savedUser->status);
    }

    public function test_validation_prevents_duplicate_citizen_id_or_employee_code(): void
    {
        $controller = app(AdminUserController::class);

        // Tạo user đầu tiên
        $req1 = Request::create('/admin_user_store', 'POST', [
            'name' => 'Cán bộ 1',
            'citizen_id' => '087095009999',
            'employee_code' => 'NV-9999',
            'email' => 'cb1@example.com',
            'password' => 'password123',
            'branch_id' => 1,
            'role_id' => 2,
            'document_role' => 'user',
        ]);
        $controller->store($req1);

        // Thử tạo user thứ 2 với cùng citizen_id
        $reqDuplicateCitizen = Request::create('/admin_user_store', 'POST', [
            'name' => 'Cán bộ 2',
            'citizen_id' => '087095009999', // Trùng
            'employee_code' => 'NV-8888',
            'email' => 'cb2@example.com',
            'password' => 'password123',
            'branch_id' => 1,
            'role_id' => 2,
            'document_role' => 'user',
        ]);

        try {
            $controller->store($reqDuplicateCitizen);
            $this->fail('Expected ValidationException for duplicate citizen_id');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('citizen_id', $e->errors());
        }

        // Thử tạo user thứ 3 với cùng employee_code
        $reqDuplicateEmployeeCode = Request::create('/admin_user_store', 'POST', [
            'name' => 'Cán bộ 3',
            'citizen_id' => '087095008888',
            'employee_code' => 'NV-9999', // Trùng
            'email' => 'cb3@example.com',
            'password' => 'password123',
            'branch_id' => 1,
            'role_id' => 2,
            'document_role' => 'user',
        ]);

        try {
            $controller->store($reqDuplicateEmployeeCode);
            $this->fail('Expected ValidationException for duplicate employee_code');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('employee_code', $e->errors());
        }
    }

    public function test_admin_can_edit_and_update_user_profile(): void
    {
        $user = User::create([
            'name' => 'Nguyễn Ban Đầu',
            'citizen_id' => '087095001111',
            'employee_code' => 'NV-OLD-01',
            'email' => 'old@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => 1,
            'role_id' => 2,
            'document_role' => 'user',
            'status' => 'active',
            'phone_no' => '0909998888',
        ]);

        $controller = app(AdminUserController::class);

        // 1. Kiểm tra API edit trả về các trường an toàn và không chứa password
        $reqEdit = Request::create('/admin_user_edit', 'GET', ['user_id' => $user->id]);
        $editRes = $controller->edit($reqEdit);
        $userData = $editRes->getData()->user;

        $this->assertSame($user->id, $userData->id);
        $this->assertSame('087095001111', $userData->citizen_id);
        $this->assertSame('NV-OLD-01', $userData->employee_code);
        $this->assertSame('0909998888', $userData->phone_no);
        $this->assertObjectNotHasProperty('password', $userData);

        // 2. Cập nhật thông tin mới
        $reqUpdate = Request::create('/admin_user_update', 'POST', [
            'user_id' => $user->id,
            'name' => 'Nguyễn Đã Cập Nhật',
            'citizen_id' => '087095002222',
            'employee_code' => 'NV-NEW-02',
            'email' => 'updated@example.com',
            'branch_id' => 1,
            'role_id' => 2,
            'document_role' => 'clerk',
            'phone_no' => '0933221100',
        ]);

        $updateRes = $controller->update($reqUpdate);
        $this->assertTrue($updateRes->getData()->status);

        $refreshed = $user->fresh();
        $this->assertSame('Nguyễn Đã Cập Nhật', $refreshed->name);
        $this->assertSame('087095002222', $refreshed->citizen_id);
        $this->assertSame('NV-NEW-02', $refreshed->employee_code);
        $this->assertSame('updated@example.com', $refreshed->email);
        $this->assertSame('clerk', $refreshed->document_role);
        $this->assertSame('0933221100', $refreshed->phone_no);
    }

    public function test_keyword_search_matches_citizen_id_and_employee_code(): void
    {
        User::create([
            'name' => 'Trần Văn Tìm Kiếm',
            'citizen_id' => '079099887766',
            'employee_code' => 'NV-TARGET-99',
            'email' => 'target@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => 1,
            'role_id' => 2,
            'status' => 'active',
            'phone_no' => '0977889900',
        ]);

        $controller = app(AdminUserController::class);

        // Tìm theo citizen_id
        $reqCitizen = Request::create('/admin/list_staff/data', 'GET', ['keyword' => '079099887766']);
        $this->app->instance('request', $reqCitizen);
        $res1 = $controller->data($reqCitizen)->getData(true);
        $this->assertSame(1, $res1['recordsFiltered']);
        $this->assertSame('Trần Văn Tìm Kiếm', $res1['data'][0]['name']);

        // Tìm theo employee_code
        $reqCode = Request::create('/admin/list_staff/data', 'GET', ['keyword' => 'NV-TARGET-99']);
        $this->app->instance('request', $reqCode);
        $res2 = $controller->data($reqCode)->getData(true);
        $this->assertSame(1, $res2['recordsFiltered']);
        $this->assertSame('Trần Văn Tìm Kiếm', $res2['data'][0]['name']);

        // Tìm theo phone_no
        $reqPhone = Request::create('/admin/list_staff/data', 'GET', ['keyword' => '0977889900']);
        $this->app->instance('request', $reqPhone);
        $res3 = $controller->data($reqPhone)->getData(true);
        $this->assertSame(1, $res3['recordsFiltered']);
        $this->assertSame('Trần Văn Tìm Kiếm', $res3['data'][0]['name']);
    }

    public function test_admin_can_store_and_update_user_using_only_role_ids_with_auto_derivation(): void
    {
        $controller = app(AdminUserController::class);

        // 1. Tạo user chỉ với role_ids (Staff + Clerk), không truyền role_id hay document_role
        $storeReq = Request::create('/admin_user_store', 'POST', [
            'name' => 'Lê Văn Thư',
            'email' => 'vanthu@example.com',
            'password' => 'secret123',
            'branch_id' => 1,
            'role_ids' => [3, 4], // Staff (3) & Clerk (4)
        ]);

        $storeRes = $controller->store($storeReq);
        $this->assertTrue($storeRes->getData()->status);

        $user = User::where('email', 'vanthu@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(2, (int) $user->role_id);
        $this->assertSame('clerk', $user->document_role);
        $this->assertTrue($user->isClerk());
        $this->assertSame([3, 4], $user->roles()->pluck('roles.id')->sort()->values()->toArray());

        // 2. Cập nhật thành Supervisor (2), bỏ vai trò Clerk
        $updateReq = Request::create('/admin_user_update', 'POST', [
            'user_id' => $user->id,
            'name' => 'Lê Kiểm Soát',
            'email' => 'kiemsoat@example.com',
            'branch_id' => 1,
            'role_ids' => [2], // Chỉ có Supervisor (2)
        ]);

        $updateRes = $controller->update($updateReq);
        $this->assertTrue($updateRes->getData()->status);

        $user->refresh();
        $this->assertSame(1, (int) $user->role_id);
        $this->assertSame('user', $user->document_role);
        $this->assertFalse($user->isClerk());
        $this->assertSame([2], $user->roles()->pluck('roles.id')->values()->toArray());
    }

    public function test_edit_legacy_user_automatically_populates_role_ids(): void
    {
        // Tạo tài khoản legacy chỉ có role_id và document_role, không có bản ghi user_role
        $legacyUser = User::create([
            'name' => 'Cán Bộ Cũ',
            'email' => 'legacy@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => 1,
            'role_id' => 1, // Supervisor
            'document_role' => 'clerk', // Clerk
            'status' => 'active',
        ]);

        $this->assertCount(0, $legacyUser->roles);

        $controller = app(AdminUserController::class);
        $req = Request::create('/admin_user_edit', 'GET', ['user_id' => $legacyUser->id]);
        $response = $controller->edit($req);

        $data = $response->getData(true);
        // Tự động map fallback sang ID của role supervisor (2) và clerk (4)
        $this->assertContains(2, $data['role_ids']);
        $this->assertContains(4, $data['role_ids']);
    }

    public function test_validation_requires_at_least_one_role_when_both_role_ids_and_role_id_missing(): void
    {
        $controller = app(AdminUserController::class);
        $req = Request::create('/admin_user_store', 'POST', [
            'name' => 'Không Vai Trò',
            'email' => 'norole@example.com',
            'password' => 'secret123',
            'branch_id' => 1,
            // Không truyền cả role_ids lẫn role_id
        ]);

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }
}
