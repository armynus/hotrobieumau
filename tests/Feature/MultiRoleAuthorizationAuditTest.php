<?php

namespace Tests\Feature;

use App\Models\Branches;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiRoleAuthorizationAuditTest extends TestCase
{
    private User $staffUser;
    private User $clerkUser;
    private User $supervisorUser;
    private User $itAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permissions are active
        Permission::query()->update(['status' => 'active']);
        Cache::flush();
        Role::bumpPermissionsVersion();

        $branchType1 = Branches::firstOrCreate(
            ['branch_code' => 'TEST_BR_T1'],
            ['branch_name' => 'Chi nhánh Loại 1 Test', 'branch_type' => 'type_1', 'status' => 'active']
        );

        $branchType2 = Branches::firstOrCreate(
            ['branch_code' => 'TEST_BR_T2'],
            ['branch_name' => 'Chi nhánh Loại 2 Test', 'branch_type' => 'type_2', 'status' => 'active']
        );

        $staffRole = Role::where('slug', 'staff')->firstOrFail();
        $clerkRole = Role::where('slug', 'clerk')->firstOrFail();
        $supervisorRole = Role::where('slug', 'supervisor')->firstOrFail();
        $itAdminRole = Role::where('slug', 'it_admin')->firstOrFail();
        $itAdminPermIds = Permission::whereIn('slug', [
            'it_support.request', 'it_support.templates', 'it_support.manage', 'it_support.categories_manage',
            'forms.view', 'forms.print', 'data.merger_lookup', 'system.users.view',
        ])->pluck('id');
        $itAdminRole->permissions()->sync($itAdminPermIds);

        // 1. Staff user
        $this->staffUser = User::firstOrCreate(
            ['email' => 'audit_staff@bidv.com.vn'],
            ['name' => 'Nhân viên Audit', 'password' => bcrypt('password'), 'role_id' => 2, 'status' => 'active']
        );
        $this->staffUser->branch_id = $branchType1->id;
        $this->staffUser->save();
        $this->staffUser->roles()->sync([$staffRole->id]);

        // 2. Clerk user (Branch Type 1)
        $this->clerkUser = User::firstOrCreate(
            ['email' => 'audit_clerk@bidv.com.vn'],
            ['name' => 'Văn thư Audit', 'password' => bcrypt('password'), 'role_id' => 2, 'document_role' => 'clerk', 'status' => 'active']
        );
        $this->clerkUser->branch_id = $branchType1->id;
        $this->clerkUser->save();
        $this->clerkUser->roles()->sync([$clerkRole->id]);

        // 3. Supervisor user
        $this->supervisorUser = User::firstOrCreate(
            ['email' => 'audit_supervisor@bidv.com.vn'],
            ['name' => 'Kiểm soát viên Audit', 'password' => bcrypt('password'), 'role_id' => 1, 'status' => 'active']
        );
        $this->supervisorUser->branch_id = $branchType1->id;
        $this->supervisorUser->save();
        $this->supervisorUser->roles()->sync([$supervisorRole->id]);

        // 4. IT Admin user
        $this->itAdminUser = User::firstOrCreate(
            ['email' => 'audit_itadmin@bidv.com.vn'],
            ['name' => 'Quản trị IT Audit', 'password' => bcrypt('password'), 'role_id' => 2, 'status' => 'active']
        );
        $this->itAdminUser->branch_id = $branchType1->id;
        $this->itAdminUser->save();
        $this->itAdminUser->roles()->sync([$itAdminRole->id]);
    }

    private function sessionData(User $user): array
    {
        return [
            'user_id' => $user->id,
            'user_role' => $user->role_id,
            'user_permissions' => $user->getAllPermissionSlugs(),
            'user_permissions_version' => Role::permissionsVersion(),
            'UserBranchId' => $user->branch_id,
            'UserBranchName' => 'Test Branch',
            'UserBranchCode' => 'TEST_BR_T1',
        ];
    }

    public function test_staff_role_permissions_and_ui_synchronization(): void
    {
        $session = $this->sessionData($this->staffUser);

        // --- BACKEND ALLOWED ---
        $this->withSession($session)->get(route('support_forms.catalog'))->assertOk();
        $this->withSession($session)->get(route('documents_forward'))->assertOk();
        $this->withSession($session)->get(route('documents_incoming'))->assertOk();
        $this->withSession($session)->get(route('view_data_customer'))->assertOk();
        $this->withSession($session)->get(route('view_data_account'))->assertOk();
        $this->withSession($session)->get(route('user.it_support.templates.index'))->assertOk();
        $this->withSession($session)->get(route('user.it_support.index'))->assertOk();

        // --- BACKEND DENIED (Strict rejection on URL entry) ---
        // Sổ văn bản (documents.manage_ledger)
        $this->withSession($session)->get(route('documents_ledger'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');

        // Đăng văn bản (documents.upload)
        $this->withSession($session)->get(route('documents_incoming_register'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');

        // Báo cáo văn bản (documents.reports)
        $this->withSession($session)->get(route('document_reports'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');

        // Upload khách hàng (data.customers.upload)
        $this->withSession($session)->post(route('uploadfile_customer'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');

        // --- UI SYNCHRONIZATION ---
        // Sidebar does NOT show ledger, register, report
        $sidebar = $this->withSession($session)->view('user.layouts.sidebar');
        $sidebar->assertDontSee(route('documents_ledger'));
        $sidebar->assertDontSee(route('documents_incoming_register'));
        $sidebar->assertDontSee(route('document_reports'));

        // Customer page does NOT show upload button/modal
        $custRes = $this->withSession($session)->get(route('view_data_customer'))->assertOk();
        $custRes->assertDontSee('Đăng tải dữ liệu Excel');
        $custRes->assertDontSee('id="uploadCustomModal"', false);

        // Account page does NOT show upload button/modal
        $accRes = $this->withSession($session)->get(route('view_data_account'))->assertOk();
        $accRes->assertDontSee('Đăng tải dữ liệu Excel');
        $accRes->assertDontSee('id="uploadAccountModal"', false);
    }

    public function test_clerk_role_permissions_and_ui_synchronization(): void
    {
        $session = $this->sessionData($this->clerkUser);

        // --- BACKEND ALLOWED ---
        $this->withSession($session)->get(route('documents_ledger'))->assertOk();
        $this->withSession($session)->get(route('document_reports'))->assertOk();
        $this->withSession($session)->get(route('documents_incoming_register'))->assertOk();
        $this->withSession($session)->get(route('documents_ledger_candidates', ['q' => 'test']))->assertOk();

        // --- BACKEND DENIED ---
        // Clerk does not have customer/account upload permission
        $this->withSession($session)->post(route('uploadfile_customer'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');

        $this->withSession($session)->post(route('uploadfile_account'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');

        // --- UI SYNCHRONIZATION ---
        // Sidebar DOES show ledger, register, report
        $sidebar = $this->withSession($session)->view('user.layouts.sidebar');
        $sidebar->assertSee(route('documents_ledger'));
        $sidebar->assertSee(route('documents_incoming_register'));
        $sidebar->assertSee(route('document_reports'));
    }

    public function test_supervisor_role_permissions_and_ui_synchronization(): void
    {
        $session = $this->sessionData($this->supervisorUser);

        // --- BACKEND ALLOWED ---
        $this->withSession($session)->get(route('view_data_customer'))->assertOk();
        $this->withSession($session)->get(route('view_data_account'))->assertOk();
        $this->withSession($session)->get(route('scan_qr_code'))->assertOk();
        $this->withSession($session)->get(route('merger_lookup'))->assertOk();

        // --- BACKEND DENIED ---
        // Supervisor does not have documents.manage_ledger
        $this->withSession($session)->get(route('documents_ledger'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');

        // --- UI SYNCHRONIZATION ---
        // Customer page DOES show upload button
        $custRes = $this->withSession($session)->get(route('view_data_customer'))->assertOk();
        $custRes->assertSee('Đăng tải dữ liệu Excel');
        $custRes->assertSee('id="uploadCustomModal"', false);

        // Sidebar does NOT show ledger
        $sidebar = $this->withSession($session)->view('user.layouts.sidebar');
        $sidebar->assertDontSee(route('documents_ledger'));
    }

    public function test_it_admin_role_permissions_and_ui_synchronization(): void
    {
        $session = $this->sessionData($this->itAdminUser);

        // --- BACKEND ALLOWED ---
        $this->withSession($session)->get(route('user.it_support.templates.index'))->assertOk();
        $this->withSession($session)->get(route('user.it_support.index'))->assertOk();
        $this->withSession($session)->get(route('user.it_support.create'))->assertOk();

        // --- BACKEND DENIED ---
        // IT Admin does not have customer upload or document ledger
        $this->withSession($session)->get(route('documents_ledger'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');

        $this->withSession($session)->post(route('uploadfile_customer'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');
    }

    public function test_feature_global_toggle_immediately_blocks_and_hides_even_for_permitted_roles(): void
    {
        $supervisorSession = $this->sessionData($this->supervisorUser);

        // 1. Initially active: allowed
        $this->withSession($supervisorSession)->get(route('merger_lookup'))->assertOk();
        $sidebar = $this->withSession($supervisorSession)->view('user.layouts.sidebar');
        $sidebar->assertSee(route('merger_lookup'));

        // 2. Toggle inactive globally
        Permission::where('slug', 'data.merger_lookup')->update(['status' => 'inactive']);
        Cache::forget('perm_active_data.merger_lookup');
        Role::bumpPermissionsVersion();

        // Backend must block direct URL
        $this->withSession($supervisorSession)->get(route('merger_lookup'))
            ->assertRedirect(route('index'))
            ->assertSessionHas('error');

        // UI must hide link
        $sidebarInactive = $this->withSession($supervisorSession)->view('user.layouts.sidebar');
        $sidebarInactive->assertDontSee(route('merger_lookup'));

        // 3. Toggle back to active
        Permission::where('slug', 'data.merger_lookup')->update(['status' => 'active']);
        Cache::forget('perm_active_data.merger_lookup');
        Role::bumpPermissionsVersion();

        $this->withSession($supervisorSession)->get(route('merger_lookup'))->assertOk();
        $sidebarActiveAgain = $this->withSession($supervisorSession)->view('user.layouts.sidebar');
        $sidebarActiveAgain->assertSee(route('merger_lookup'));
    }
}
