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
}
