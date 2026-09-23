<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminTransactionOfficeController;
use App\Models\TransactionOffice;
use App\Models\Users;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrganizationAdministrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'organization_testing', 'database.connections.organization_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('organization_testing');

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_name');
            $table->string('branch_type');
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('department_name');
            $table->string('department_code')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('positions', function (Blueprint $table): void {
            $table->id();
            $table->string('position_name');
            $table->string('position_code')->nullable();
            $table->unsignedInteger('level')->default(999);
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('transaction_offices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('office_name');
            $table->string('office_code')->nullable();
            $table->string('office_address')->nullable();
            $table->string('office_place')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('office_fax')->nullable();
            $table->string('office_email')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('transaction_office_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('user_ipcas')->nullable();
            $table->string('password');
            $table->string('role_id');
            $table->string('document_role')->default('user');
            $table->integer('failed_login_attempts')->default(0);
            $table->string('status');
            $table->timestamps();
        });

        DB::table('branches')->insert([
            ['id' => 1, 'branch_name' => 'Chi nhánh loại I', 'branch_type' => 'type_1', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'branch_name' => 'Chi nhánh loại II A', 'branch_type' => 'type_2', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'branch_name' => 'Chi nhánh loại II B', 'branch_type' => 'type_2', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_type_two_department_command_is_idempotent_and_can_target_one_branch(): void
    {
        $this->artisan('organization:seed-type2-departments', ['--branch' => [2]])->assertSuccessful();

        $this->assertSame(4, DB::table('departments')->where('branch_id', 2)->count());
        $this->assertSame(0, DB::table('departments')->where('branch_id', 3)->count());
        $this->assertDatabaseHas('departments', [
            'branch_id' => 2,
            'department_code' => 'KTNQ',
            'department_name' => 'Phòng Kế toán Ngân quỹ',
        ]);

        $this->artisan('organization:seed-type2-departments', ['--branch' => [2]])->assertSuccessful();
        $this->assertSame(4, DB::table('departments')->where('branch_id', 2)->count());
    }

    public function test_admin_can_store_optional_user_metadata_but_not_a_department_from_another_branch(): void
    {
        DB::table('departments')->insert([
            'id' => 10, 'branch_id' => 3, 'department_name' => 'Phòng khác', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $invalid = Request::create('/admin_user_store', 'POST', [
            'name' => 'Nguyễn Văn A', 'email' => 'a@example.test', 'user_ipcas' => 'IPCAS-A',
            'password' => 'secret1', 'branch_id' => 2, 'department_id' => 10,
            'role_id' => 2, 'document_role' => 'user',
        ]);

        try {
            app(AdminUserController::class)->store($invalid);
            $this->fail('Phòng ban khác chi nhánh phải bị từ chối.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('department_id', $exception->errors());
        }

        $valid = Request::create('/admin_user_store', 'POST', [
            'name' => 'Nguyễn Văn A', 'email' => 'a@example.test', 'user_ipcas' => 'IPCAS-A',
            'password' => 'secret1', 'branch_id' => 2, 'department_id' => null, 'position_id' => null,
            'role_id' => 2, 'document_role' => 'user',
        ]);
        $response = app(AdminUserController::class)->store($valid);
        $user = Users::sole();

        $this->assertTrue($response->getData(true)['status']);
        $this->assertSame('IPCAS-A', $user->user_ipcas);
        $this->assertNull($user->department_id);
        $this->assertNull($user->position_id);
    }

    public function test_admin_can_manage_transaction_office_contact_information(): void
    {
        $request = Request::create('/admin/transaction-offices', 'POST', [
            'branch_id' => 2,
            'office_name' => 'Phòng giao dịch Sa Đéc',
            'office_code' => 'PGD-SD',
            'office_address' => '12 Nguyễn Huệ, phường Sa Đéc',
            'office_place' => 'Đồng Tháp',
            'office_phone' => '0277 123 4567',
            'office_fax' => '0277 765 4321',
            'office_email' => 'sadec@example.test',
            'manager_name' => 'Nguyễn Văn B',
            'status' => 'active',
        ]);

        $response = app(AdminTransactionOfficeController::class)->store($request);
        $office = TransactionOffice::sole();

        $this->assertTrue($response->getData(true)['status']);
        $this->assertSame(2, $office->branch_id);
        $this->assertSame('12 Nguyễn Huệ, phường Sa Đéc', $office->office_address);
        $this->assertSame('sadec@example.test', $office->office_email);

        $update = Request::create('/admin/transaction-offices/update', 'POST', [
            'transaction_office_id' => $office->id,
            'branch_id' => 2,
            'office_name' => 'Phòng giao dịch Sa Đéc',
            'office_code' => 'PGD-SD',
            'office_phone' => '0277 999 0000',
            'status' => 'inactive',
        ]);
        app(AdminTransactionOfficeController::class)->update($update);

        $this->assertDatabaseHas('transaction_offices', [
            'id' => $office->id,
            'office_phone' => '0277 999 0000',
            'status' => 'inactive',
        ]);
    }

    public function test_user_transaction_office_must_belong_to_selected_branch(): void
    {
        DB::table('transaction_offices')->insert([
            ['id' => 20, 'branch_id' => 3, 'office_name' => 'PGD khác chi nhánh', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'branch_id' => 2, 'office_name' => 'PGD đúng chi nhánh', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $invalid = Request::create('/admin_user_store', 'POST', [
            'name' => 'Nguyễn Văn C', 'email' => 'c@example.test', 'password' => 'secret1',
            'branch_id' => 2, 'transaction_office_id' => 20,
            'role_id' => 2, 'document_role' => 'user',
        ]);

        try {
            app(AdminUserController::class)->store($invalid);
            $this->fail('Phòng giao dịch khác chi nhánh phải bị từ chối.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('transaction_office_id', $exception->errors());
        }

        $valid = Request::create('/admin_user_store', 'POST', [
            'name' => 'Nguyễn Văn C', 'email' => 'c@example.test', 'password' => 'secret1',
            'branch_id' => 2, 'transaction_office_id' => 21,
            'role_id' => 2, 'document_role' => 'user',
        ]);
        app(AdminUserController::class)->store($valid);

        $this->assertSame(21, Users::where('email', 'c@example.test')->value('transaction_office_id'));

        $move = Request::create('/admin/transaction-offices/update', 'POST', [
            'transaction_office_id' => 21,
            'branch_id' => 3,
            'office_name' => 'PGD đúng chi nhánh',
            'status' => 'active',
        ]);
        try {
            app(AdminTransactionOfficeController::class)->update($move);
            $this->fail('PGD đã gán nhân viên không được chuyển sang chi nhánh khác.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('branch_id', $exception->errors());
        }
        $this->assertSame(2, TransactionOffice::findOrFail(21)->branch_id);
    }
}
