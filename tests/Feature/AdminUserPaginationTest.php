<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'admin_user_testing', 'database.connections.admin_user_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('admin_user_testing');

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name');
            $table->string('branch_type')->default('type_2');
            $table->string('status')->default('active');
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('department_name');
            $table->string('department_code')->nullable();
            $table->string('status')->default('active');
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('position_name');
            $table->string('position_code')->nullable();
            $table->unsignedInteger('level')->default(999);
            $table->string('status')->default('active');
        });
        Schema::create('transaction_offices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('office_name');
            $table->string('office_code')->nullable();
            $table->string('status')->default('active');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('citizen_id')->nullable();
            $table->string('employee_code')->nullable();
            $table->string('email');
            $table->string('user_ipcas')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('transaction_office_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedTinyInteger('role_id');
            $table->string('document_role')->default('user');
            $table->string('status');
            $table->timestamps();
        });
        DB::table('branches')->insert(['id' => 1, 'branch_name' => 'Chi nhánh kiểm thử']);
        foreach (range(1, 25) as $number) {
            DB::table('users')->insert([
                'name' => 'Người dùng '.$number,
                'email' => 'user'.$number.'@example.test',
                'branch_id' => 1,
                'role_id' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_admin_page_does_not_load_every_user_and_data_endpoint_pages_on_the_server(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(AdminUserController::class)->index();
        $indexQueries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertFalse(collect($indexQueries)->contains(
            fn (array $query) => str_contains(strtolower($query['query']), 'from "users"')
        ));

        $request = Request::create('/admin/list_staff/data', 'GET', [
            'draw' => 1,
            'start' => 10,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'columns' => [
                ['data' => 'id', 'name' => 'users.id', 'searchable' => 'false', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'name', 'name' => 'users.name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'email', 'name' => 'users.email', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'branch_name', 'name' => 'branches.branch_name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ],
            'order' => [['column' => 1, 'dir' => 'asc']],
        ]);
        $this->app->instance('request', $request);

        $response = app(AdminUserController::class)->data($request);
        $payload = $response->getData(true);

        $this->assertSame(25, $payload['recordsTotal']);
        $this->assertSame(25, $payload['recordsFiltered']);
        $this->assertCount(10, $payload['data']);
        $this->assertSame('Người dùng 19', $payload['data'][0]['name']);
        $this->assertSame('Chi nhánh kiểm thử', $payload['data'][0]['branch_name']);

        $request->query->set('start', 0);
        $request->query->set('search', ['value' => 'user23@example.test', 'regex' => 'false']);
        $this->app->instance('request', $request);
        $filtered = app(AdminUserController::class)->data($request)->getData(true);
        $this->assertSame(1, $filtered['recordsFiltered']);
        $this->assertSame('user23@example.test', $filtered['data'][0]['email']);
    }

    public function test_admin_user_data_supports_multi_criteria_filters_and_returns_stats(): void
    {
        $branch2Id = DB::table('branches')->insertGetId(['branch_name' => 'Chi nhánh Sa Đéc', 'branch_type' => 'type_2']);
        $deptId = DB::table('departments')->insertGetId(['branch_id' => 1, 'department_name' => 'Phòng Khách hàng']);
        $posId = DB::table('positions')->insertGetId(['position_name' => 'Trưởng phòng']);

        // Insert distinct users for testing filters
        DB::table('users')->insert([
            'name' => 'Trần Văn Clerk',
            'email' => 'clerk@example.test',
            'user_ipcas' => 'CLK001',
            'branch_id' => 1,
            'department_id' => $deptId,
            'position_id' => $posId,
            'role_id' => 2,
            'document_role' => 'clerk',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Lê Quản Trị',
            'email' => 'admin@example.test',
            'user_ipcas' => 'ADM001',
            'branch_id' => $branch2Id,
            'role_id' => 0,
            'document_role' => 'user',
            'status' => 'inactive',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Filter by branch_id = $branch2Id
        $req = Request::create('/admin/list_staff/data', 'GET', ['branch_id' => $branch2Id]);
        $this->app->instance('request', $req);
        $res = app(AdminUserController::class)->data($req)->getData(true);
        $this->assertSame(1, $res['recordsFiltered']);
        $this->assertSame('Lê Quản Trị', $res['data'][0]['name']);

        // 2. Filter by document_role = 'clerk'
        $req = Request::create('/admin/list_staff/data', 'GET', ['document_role' => 'clerk']);
        $this->app->instance('request', $req);
        $res = app(AdminUserController::class)->data($req)->getData(true);
        $this->assertSame(1, $res['recordsFiltered']);
        $this->assertSame('Trần Văn Clerk', $res['data'][0]['name']);

        // 3. Filter by role_id = 0 (Admin)
        $req = Request::create('/admin/list_staff/data', 'GET', ['role_id' => 0]);
        $this->app->instance('request', $req);
        $res = app(AdminUserController::class)->data($req)->getData(true);
        $this->assertSame(1, $res['recordsFiltered']);
        $this->assertSame('Lê Quản Trị', $res['data'][0]['name']);

        // 4. Filter by status = 'inactive'
        $req = Request::create('/admin/list_staff/data', 'GET', ['status' => 'inactive']);
        $this->app->instance('request', $req);
        $res = app(AdminUserController::class)->data($req)->getData(true);
        $this->assertSame(1, $res['recordsFiltered']);
        $this->assertSame('Lê Quản Trị', $res['data'][0]['name']);

        // 5. Filter by department_id
        $req = Request::create('/admin/list_staff/data', 'GET', ['department_id' => $deptId]);
        $this->app->instance('request', $req);
        $res = app(AdminUserController::class)->data($req)->getData(true);
        $this->assertSame(1, $res['recordsFiltered']);
        $this->assertSame('Trần Văn Clerk', $res['data'][0]['name']);

        // 6. Filter by position_id
        $req = Request::create('/admin/list_staff/data', 'GET', ['position_id' => $posId]);
        $this->app->instance('request', $req);
        $res = app(AdminUserController::class)->data($req)->getData(true);
        $this->assertSame(1, $res['recordsFiltered']);
        $this->assertSame('Trần Văn Clerk', $res['data'][0]['name']);

        // 7. Filter by keyword (IPCAS)
        $req = Request::create('/admin/list_staff/data', 'GET', ['keyword' => 'CLK001']);
        $this->app->instance('request', $req);
        $res = app(AdminUserController::class)->data($req)->getData(true);
        $this->assertSame(1, $res['recordsFiltered']);
        $this->assertSame('Trần Văn Clerk', $res['data'][0]['name']);

        // 8. Stats check
        $this->assertArrayHasKey('stats', $res);
        $this->assertSame(27, $res['stats']['total']);
        $this->assertSame(26, $res['stats']['active']);
        $this->assertSame(1, $res['stats']['inactive']);
        $this->assertSame(1, $res['stats']['clerk']);
        $this->assertSame(1, $res['stats']['admin']);
    }
}
