<?php

namespace Tests\Feature;

use App\Models\Branches;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class RbacSystemTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_rbac_tables_have_seeded_data(): void
    {
        $this->assertGreaterThanOrEqual(6, Role::count());
        $this->assertGreaterThanOrEqual(26, Permission::count());

        $adminRole = Role::where('slug', 'admin')->first();
        $this->assertNotNull($adminRole);
        $this->assertTrue($adminRole->is_system);

        $clerkRole = Role::where('slug', 'clerk')->first();
        $this->assertNotNull($clerkRole);

        // Verify key permissions exist
        $this->assertNotNull(Permission::where('slug', 'documents.upload')->first());
        $this->assertNotNull(Permission::where('slug', 'forms.view')->first());
        $this->assertNotNull(Permission::where('slug', 'it_support.manage')->first());
        $this->assertNotNull(Permission::where('slug', 'data.scan_qr')->first());
        $this->assertNotNull(Permission::where('slug', 'system.roles.manage')->first());
    }

    public function test_user_has_role_and_permission_checks(): void
    {
        $branch = Branches::first() ?? Branches::create([
            'branch_name' => 'Chi nhánh Test',
            'branch_code' => '9999',
            'branch_type' => 'type_1',
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Cán bộ Test RBAC',
            'email' => 'test_rbac_' . uniqid() . '@agribank.com.vn',
            'password' => bcrypt('123456'),
            'branch_id' => $branch->id,
            'role_id' => 2, // Staff
            'document_role' => 'user',
            'status' => 'active',
        ]);

        $clerkRole = Role::where('slug', 'clerk')->first();
        $user->roles()->sync([$clerkRole->id]);

        $this->assertTrue($user->hasRole('clerk'));
        $this->assertTrue($user->hasPermission('documents.upload'));
        $this->assertFalse($user->hasPermission('system.roles.manage'));

        // Admin role check
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin_test_' . uniqid() . '@agribank.com.vn',
            'password' => bcrypt('123456'),
            'branch_id' => $branch->id,
            'role_id' => 0,
            'document_role' => 'user',
            'status' => 'active',
        ]);

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasPermission('system.roles.manage'));
        $this->assertTrue($admin->hasPermission('anything.at.all'));
    }

    public function test_admin_can_view_roles_and_permission_matrix(): void
    {
        $response = $this->withSession(['admin_id' => 1])
            ->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertSee('Quản trị Phân quyền Hệ thống');
        $response->assertSee('Ma trận Phân quyền');
        $response->assertSee('Quản trị viên Hệ thống');
        $response->assertSee('Văn thư chi nhánh');
    }

    public function test_admin_can_create_update_and_delete_custom_role(): void
    {
        // 1. Create custom role
        $perm = Permission::first();
        $createResponse = $this->withSession(['admin_id' => 1])
            ->postJson(route('admin.roles.store'), [
                'name' => 'Chuyên viên Thanh toán Quốc tế ' . uniqid(),
                'description' => 'Vai trò tùy biến thử nghiệm',
                'permission_ids' => [$perm->id],
            ]);

        $createResponse->assertStatus(200);
        $createResponse->assertJson(['status' => true]);
        $roleId = $createResponse->json('role.id');
        $this->assertNotNull($roleId);

        // 2. Update role matrix
        $allPermIds = Permission::pluck('id')->take(5)->toArray();
        $matrixResponse = $this->withSession(['admin_id' => 1])
            ->postJson(route('admin.roles.matrix', ['role' => $roleId]), [
                'permission_ids' => $allPermIds,
            ]);

        $matrixResponse->assertStatus(200);
        $matrixResponse->assertJson(['status' => true, 'permission_count' => 5]);

        // 3. Delete custom role
        $deleteResponse = $this->withSession(['admin_id' => 1])
            ->deleteJson(route('admin.roles.destroy', ['role' => $roleId]));

        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson(['status' => true]);
        $this->assertNull(Role::find($roleId));
    }

    public function test_system_core_roles_cannot_be_deleted(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $response = $this->withSession(['admin_id' => 1])
            ->deleteJson(route('admin.roles.destroy', ['role' => $adminRole->id]));

        $response->assertStatus(422);
        $response->assertJson(['status' => false]);
    }

    public function test_check_permission_middleware_enforcement(): void
    {
        $branch = Branches::first() ?? Branches::create([
            'branch_name' => 'Chi nhánh Test',
            'branch_code' => '8888',
            'branch_type' => 'type_1',
            'status' => 'active',
        ]);

        $userWithoutPerm = User::create([
            'name' => 'User No Perm',
            'email' => 'noperm_' . uniqid() . '@agribank.com.vn',
            'password' => bcrypt('123456'),
            'branch_id' => $branch->id,
            'role_id' => 2,
            'document_role' => 'user',
            'status' => 'active',
        ]);

        // Test middleware directly
        $middleware = new \App\Http\Middleware\CheckPermission();

        // 1. Unauthenticated -> redirect or 401
        $req1 = Request::create('/test-perm', 'GET');
        $res1 = $middleware->handle($req1, fn() => response('OK'), 'documents.upload');
        $this->assertTrue($res1->isRedirect());

        // 2. User with session without perm -> 403 json
        $req2 = Request::create('/test-perm', 'GET');
        $req2->headers->set('Accept', 'application/json');
        $req2->setLaravelSession(app('session.store'));
        $req2->session()->put('user_id', $userWithoutPerm->id);
        $req2->session()->put('user_permissions', []);

        $res2 = $middleware->handle($req2, fn() => response('OK'), 'system.roles.manage');
        $this->assertEquals(403, $res2->getStatusCode());

        // 3. User with perm in session -> allowed
        $req3 = Request::create('/test-perm', 'GET');
        $req3->setLaravelSession(app('session.store'));
        $req3->session()->put('user_id', $userWithoutPerm->id);
        $req3->session()->put('user_permissions', ['documents.view', 'forms.view']);

        $res3 = $middleware->handle($req3, fn() => response('OK'), 'forms.view');
        $this->assertEquals(200, $res3->getStatusCode());
        $this->assertEquals('OK', $res3->getContent());

        // 4. Pure Admin session -> allowed
        $req4 = Request::create('/test-perm', 'GET');
        $req4->setLaravelSession(app('session.store'));
        $req4->session()->forget(['user_id', 'user_permissions']);
        $req4->session()->put('admin_id', 1);

        $res4 = $middleware->handle($req4, fn() => response('OK'), 'any.permission');
        $this->assertEquals(200, $res4->getStatusCode());

        // 5. Staff user session with lingering admin_id MUST NOT bypass permissions
        $req5 = Request::create('/test-perm', 'GET');
        $req5->setLaravelSession(app('session.store'));
        $req5->session()->put('user_id', $userWithoutPerm->id);
        $req5->session()->put('admin_id', 1);
        $req5->session()->put('user_permissions', ['forms.view']);

        $res5 = $middleware->handle($req5, fn() => response('OK'), 'data.scan_qr');
        $this->assertTrue($res5->isRedirect());
    }

    public function test_helpers_and_blade_directives(): void
    {
        session(['admin_id' => 1]);
        $this->assertTrue(auth_user_can('system.roles.manage'));
        $this->assertTrue(auth_user_has_role('admin'));

        session()->forget('admin_id');
        session(['user_id' => 999, 'user_permissions' => ['forms.view', 'data.scan_qr']]);
        $this->assertTrue(auth_user_can('data.scan_qr'));
        $this->assertFalse(auth_user_can('system.roles.manage'));
    }

    public function test_admin_can_create_update_and_delete_custom_permission(): void
    {
        session(['admin_id' => 1]);

        $uniqueSlug = 'data.test_export_' . uniqid();

        // 1. Create permission
        $response = $this->postJson(route('admin.permissions.store'), [
            'name' => 'Quyền xuất dữ liệu kiểm thử',
            'slug' => $uniqueSlug,
            'module' => 'data',
            'module_name' => 'Dữ liệu Tổng hợp & Tra cứu',
            'description' => 'Mô tả quyền thử nghiệm',
            'sort_order' => 99,
            'assign_to_admin' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $perm = Permission::where('slug', $uniqueSlug)->first();
        $this->assertNotNull($perm);
        $this->assertFalse($perm->is_system);

        // Verify auto-attached to admin role
        $adminRole = Role::where('slug', 'admin')->first();
        $this->assertTrue($adminRole->permissions->contains($perm->id));

        // 2. Edit permission
        $editRes = $this->getJson(route('admin.permissions.edit', $perm->id));
        $editRes->assertStatus(200);
        $editRes->assertJsonPath('permission.name', 'Quyền xuất dữ liệu kiểm thử');

        // 3. Update permission
        $updatedSlug = $uniqueSlug . '_v2';
        $updateRes = $this->putJson(route('admin.permissions.update', $perm->id), [
            'name' => 'Quyền xuất dữ liệu kiểm thử (Đã sửa)',
            'slug' => $updatedSlug,
            'module' => 'data',
            'module_name' => 'Dữ liệu Tổng hợp & Tra cứu',
            'description' => 'Mô tả đã sửa',
            'sort_order' => 100,
        ]);

        $updateRes->assertStatus(200);
        $updateRes->assertJson(['status' => true]);
        $this->assertEquals('Quyền xuất dữ liệu kiểm thử (Đã sửa)', $perm->fresh()->name);
        $this->assertEquals($updatedSlug, $perm->fresh()->slug);

        // 4. Delete custom permission
        $delRes = $this->deleteJson(route('admin.permissions.destroy', $perm->id));
        $delRes->assertStatus(200);
        $delRes->assertJson(['status' => true]);

        $this->assertNull(Permission::find($perm->id));
    }

    public function test_system_core_permissions_cannot_be_deleted(): void
    {
        session(['admin_id' => 1]);

        $systemPerm = Permission::where('slug', 'system.roles.manage')->first();
        $this->assertNotNull($systemPerm);
        $this->assertTrue($systemPerm->is_system);

        $response = $this->deleteJson(route('admin.permissions.destroy', $systemPerm->id));
        $response->assertStatus(422);
        $response->assertJson(['status' => false]);

        $this->assertNotNull(Permission::find($systemPerm->id));
    }

    public function test_route_level_permission_enforcement(): void
    {
        $branch = Branches::first() ?? Branches::create([
            'branch_name' => 'Chi nhánh Test Route',
            'branch_code' => '7777',
            'branch_type' => 'type_1',
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Nhân viên không có quyền',
            'email' => 'staff_route_' . uniqid() . '@agribank.com.vn',
            'password' => bcrypt('123456'),
            'branch_id' => $branch->id,
            'role_id' => 2, // Staff default
            'document_role' => 'user',
            'status' => 'active',
        ]);

        // 1. Without data.scan_qr -> redirected to index with error
        $response = $this->withSession([
            'user_id' => $user->id,
            'user_permissions' => ['forms.view'],
        ])->get(route('scan_qr_code'));

        $response->assertRedirect(route('index'));
        $response->assertSessionHas('error');

        // 2. Without documents.manage_ledger -> redirected
        $response = $this->withSession([
            'user_id' => $user->id,
            'user_permissions' => ['forms.view'],
        ])->get(route('documents_ledger'));

        $response->assertRedirect(route('index'));

        // 3. Without documents.upload -> redirected
        $response = $this->withSession([
            'user_id' => $user->id,
            'user_permissions' => ['forms.view'],
        ])->get(route('documents_incoming_register'));

        $response->assertRedirect(route('index'));

        // 4. With data.scan_qr granted -> allowed access (200)
        $response = $this->withSession([
            'user_id' => $user->id,
            'user_permissions' => ['data.scan_qr'],
        ])->get(route('scan_qr_code'));

        $response->assertStatus(200);
    }

    public function test_check_permission_supports_multiple_or_permissions(): void
    {
        $middleware = new \App\Http\Middleware\CheckPermission();

        // User having only accounts.upload
        $req = Request::create('/test-multi', 'POST');
        $req->setLaravelSession(app('session.store'));
        $req->session()->put('user_id', 9999);
        $req->session()->put('user_permissions', ['data.accounts.upload']);

        // Check matching with comma or pipe separated list (OR logic)
        $res = $middleware->handle($req, fn() => response('OK'), 'data.customers.upload,data.accounts.upload');
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals('OK', $res->getContent());

        $resPipe = $middleware->handle($req, fn() => response('OK'), 'data.customers.upload|data.accounts.upload');
        $this->assertEquals(200, $resPipe->getStatusCode());

        // Check failing when user has none of the permissions
        $reqFail = Request::create('/test-multi', 'POST');
        $reqFail->headers->set('Accept', 'application/json');
        $reqFail->setLaravelSession(app('session.store'));
        $reqFail->session()->put('user_id', 9999);
        $reqFail->session()->put('user_permissions', ['forms.view']);

        $resFail = $middleware->handle($reqFail, fn() => response('OK'), 'data.customers.upload', 'data.accounts.upload');
        $this->assertEquals(403, $resFail->getStatusCode());
    }
}
