<?php

namespace Tests\Feature;

use App\Models\Branches;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LivePermissionUpdateTest extends TestCase
{
    private User $staffUser;
    private User $supervisorUser;
    private Role $staffRole;
    private Role $supervisorRole;
    private Permission $scanPerm;
    private Permission $customerUploadPerm;
    private Branches $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Role::clearPermissionsVersionCache();
        User::clearUserPermissionsVersionCache();
        Role::bumpPermissionsVersion();

        Permission::query()->update(['status' => 'active']);

        $this->branch = Branches::findOrFail(1);

        $this->staffRole = Role::where('slug', 'staff')->firstOrFail();
        $this->supervisorRole = Role::where('slug', 'supervisor')->firstOrFail();
        $this->scanPerm = Permission::where('slug', 'data.scan_qr')->firstOrFail();
        $this->customerUploadPerm = Permission::where('slug', 'data.customers.upload')->firstOrFail();

        // 1. Staff User
        $this->staffUser = User::firstOrCreate(
            ['email' => 'live_test_staff@bidv.com.vn'],
            ['name' => 'Staff Live User', 'password' => bcrypt('password'), 'role_id' => 2, 'status' => 'active']
        );
        $this->staffUser->branch_id = $this->branch->id;
        $this->staffUser->role_id = 2;
        $this->staffUser->document_role = 'user';
        $this->staffUser->status = 'active';
        $this->staffUser->save();
        $this->staffUser->roles()->sync([$this->staffRole->id]);

        // 2. Supervisor User
        $this->supervisorUser = User::firstOrCreate(
            ['email' => 'live_test_supervisor@bidv.com.vn'],
            ['name' => 'Supervisor Live User', 'password' => bcrypt('password'), 'role_id' => 1, 'status' => 'active']
        );
        $this->supervisorUser->branch_id = $this->branch->id;
        $this->supervisorUser->role_id = 1;
        $this->supervisorUser->document_role = 'user';
        $this->supervisorUser->status = 'active';
        $this->supervisorUser->save();
        $this->supervisorUser->roles()->sync([$this->supervisorRole->id]);

        // Ensure standard role permissions
        $defaultStaffPermSlugs = [
            'documents.view',
            'forms.view', 'forms.print', 'forms.bundle',
            'it_support.request', 'it_support.templates',
            'data.customers.view', 'data.accounts.view',
            'data.merger_lookup',
        ];
        $permIds = Permission::whereIn('slug', $defaultStaffPermSlugs)->pluck('id')->toArray();
        $this->staffRole->permissions()->sync($permIds);
        $this->staffRole->permissions()->detach([$this->scanPerm->id, $this->customerUploadPerm->id]);

        $defaultSupervisorPermSlugs = [
            'documents.view', 'documents.reports',
            'forms.view', 'forms.print', 'forms.bundle',
            'it_support.request', 'it_support.templates',
            'data.customers.view', 'data.customers.upload',
            'data.accounts.view', 'data.accounts.upload',
            'data.merger_lookup', 'data.scan_qr',
        ];
        $supPermIds = Permission::whereIn('slug', $defaultSupervisorPermSlugs)->pluck('id')->toArray();
        $this->supervisorRole->permissions()->sync($supPermIds);

        Role::bumpPermissionsVersion();
        User::bumpUserPermissionsVersion($this->staffUser->id);
        User::bumpUserPermissionsVersion($this->supervisorUser->id);
    }

    private function makeUserSession(User $user): array
    {
        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_role' => $user->role_id,
            'user_document_role' => $user->document_role,
            'user_permissions' => $user->getAllPermissionSlugs(true),
            'user_permissions_version' => Role::permissionsVersion(),
            'user_individual_version' => User::userPermissionsVersion($user->id),
            'user_roles' => $user->roles()->where('status', 'active')->pluck('slug')->toArray(),
            'UserBranchId' => $user->branch_id,
            'UserBranchCode' => $this->branch->branch_code,
            'UserBranchName' => $this->branch->branch_name,
        ];
    }

    /**
     * Test Case 1: Admin grants permission via Role Matrix.
     * Logged-in user sees new button and can access route on next request without logging out.
     */
    public function test_admin_grants_permission_in_role_matrix_immediately_updates_active_user_session(): void
    {
        // Session A: User is logged in as staff
        $userSession = $this->makeUserSession($this->staffUser);
        $this->assertNotContains('data.scan_qr', $userSession['user_permissions']);

        // User initially blocked from /scan_qr_code
        $response = $this->withSession($userSession)->get(route('scan_qr_code'));
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));

        // Session B: Admin updates Role Matrix to grant data.scan_qr to staff
        $currentStaffPerms = $this->staffRole->permissions()->pluck('permissions.id')->toArray();
        $newPerms = array_values(array_unique(array_merge($currentStaffPerms, [$this->scanPerm->id])));

        $adminResponse = $this->withSession(['admin_id' => 1])
            ->post(route('admin.roles.matrix', $this->staffRole->id), [
                'permission_ids' => $newPerms,
            ]);
        $adminResponse->assertJson(['status' => true]);

        // Back to Session A: User makes next request without re-login
        // User accesses /scan_qr_code -> Now allowed (200 OK)
        $userResponse = $this->withSession($userSession)->get(route('scan_qr_code'));
        $userResponse->assertOk();

        // User refreshes home page -> Sidebar contains QR Scanner link
        $homeResponse = $this->withSession($userSession)->get(route('index'));
        $homeResponse->assertOk();
        $homeResponse->assertSee(route('scan_qr_code'));
    }

    /**
     * Test Case 2: Admin revokes permission via Role Matrix.
     * Logged-in user is immediately blocked and UI elements disappear on next request.
     */
    public function test_admin_revokes_permission_in_role_matrix_immediately_blocks_active_user_session_and_hides_in_ui(): void
    {
        // First, grant permission to staff
        $this->staffRole->permissions()->syncWithoutDetaching([$this->scanPerm->id]);
        Role::bumpPermissionsVersion();

        // Session A: User logged in and has scan_qr permission
        $userSession = $this->makeUserSession($this->staffUser);
        $this->assertContains('data.scan_qr', $userSession['user_permissions']);

        // User can access /scan_qr_code
        $this->withSession($userSession)->get(route('scan_qr_code'))->assertOk();

        // Session B: Admin revokes data.scan_qr from staff
        $remainingPerms = array_values(array_diff(
            $this->staffRole->permissions()->pluck('permissions.id')->toArray(),
            [$this->scanPerm->id]
        ));

        $adminResponse = $this->withSession(['admin_id' => 1])
            ->post(route('admin.roles.matrix', $this->staffRole->id), [
                'permission_ids' => $remainingPerms,
            ]);
        $adminResponse->assertJson(['status' => true]);

        // Back to Session A: User makes next request without re-login
        // 1. Backend blocks direct access with redirect
        $blockedResponse = $this->withSession($userSession)->get(route('scan_qr_code'));
        $blockedResponse->assertStatus(302);
        $blockedResponse->assertRedirect(route('index'));
        $blockedResponse->assertSessionHas('error');

        // 2. User refreshes home page -> Sidebar does NOT contain QR Scanner link
        $homeResponse = $this->withSession($userSession)->get(route('index'));
        $homeResponse->assertOk();
        $homeResponse->assertDontSee(route('scan_qr_code'));
    }

    /**
     * Test Case 3: Admin assigns role to user via admin_user_update.
     * Logged-in user immediately receives all permissions of new role.
     */
    public function test_admin_assigns_role_to_user_immediately_grants_new_permissions_to_active_user(): void
    {
        // Session A: User logged in with staff role
        $userSession = $this->makeUserSession($this->staffUser);
        $this->assertNotContains('data.customers.upload', $userSession['user_permissions']);

        // Session B: Admin updates user to have Supervisor role
        $adminResponse = $this->withSession(['admin_id' => 1])
            ->post(route('admin_user_update'), [
                'user_id' => $this->staffUser->id,
                'name' => $this->staffUser->name,
                'email' => $this->staffUser->email,
                'branch_id' => $this->branch->id,
                'role_id' => 1,
                'document_role' => 'user',
                'role_ids' => [$this->supervisorRole->id],
            ]);
        $adminResponse->assertJson(['status' => true]);

        // Back to Session A: User makes next request without re-login
        // User refreshes view_data_customer page -> Upload button is now visible
        $customerPageResponse = $this->withSession($userSession)->get(route('view_data_customer'));
        $customerPageResponse->assertOk();
        $customerPageResponse->assertSee('Đăng tải dữ liệu Excel');
        $customerPageResponse->assertSee('uploadCustomModal');
    }

    /**
     * Test Case 4: Admin revokes role from user via admin_user_update.
     * Logged-in user immediately loses the role and its permissions.
     */
    public function test_admin_revokes_role_from_user_immediately_revokes_permissions_from_active_user(): void
    {
        // Assign supervisor role to user initially
        $this->staffUser->roles()->sync([$this->supervisorRole->id]);
        $this->staffUser->role_id = 1;
        $this->staffUser->save();
        Role::bumpPermissionsVersion();
        User::bumpUserPermissionsVersion($this->staffUser->id);

        // Session A: User logged in as supervisor
        $userSession = $this->makeUserSession($this->staffUser);
        $this->assertContains('data.customers.upload', $userSession['user_permissions']);

        // Session B: Admin revokes supervisor role, changes to regular staff
        $adminResponse = $this->withSession(['admin_id' => 1])
            ->post(route('admin_user_update'), [
                'user_id' => $this->staffUser->id,
                'name' => $this->staffUser->name,
                'email' => $this->staffUser->email,
                'branch_id' => $this->branch->id,
                'role_id' => 2,
                'document_role' => 'user',
                'role_ids' => [$this->staffRole->id],
            ]);
        $adminResponse->assertJson(['status' => true]);

        // Back to Session A: User makes next request without re-login
        // User refreshes view_data_customer page -> Upload button is now HIDDEN
        $customerPageResponse = $this->withSession($userSession)->get(route('view_data_customer'));
        $customerPageResponse->assertOk();
        $customerPageResponse->assertDontSee('Đăng tải dữ liệu Excel');
        $customerPageResponse->assertDontSee('uploadCustomModal');

        // Direct upload attempt is blocked
        $uploadAttempt = $this->withSession($userSession)->post(route('uploadfile_customer'));
        $uploadAttempt->assertStatus(302);
        $uploadAttempt->assertRedirect(route('index'));
    }

    /**
     * Test Case 5: Direct API request for revoked permission returns 403 Forbidden.
     */
    public function test_direct_api_request_for_revoked_permission_returns_403_forbidden(): void
    {
        // Session A: User logged in with stale scan_qr in session
        $userSession = $this->makeUserSession($this->staffUser);
        $userSession['user_permissions'][] = 'data.scan_qr'; // Stale session claim

        // Admin bumped version (permissions changed)
        Role::bumpPermissionsVersion();

        // User sends AJAX / JSON request to /scan_qr_code
        $apiResponse = $this->withSession($userSession)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('scan_qr_code'));

        $apiResponse->assertStatus(403);
        $apiResponse->assertJson(['status' => false]);
    }

    /**
     * Test Case 6: Admin locks user account.
     * Logged-in user is immediately blocked and redirected to login with session flushed.
     */
    public function test_admin_locks_user_immediately_redirects_and_flushes_active_user_session(): void
    {
        // Session A: User is logged in
        $userSession = $this->makeUserSession($this->staffUser);

        // Verify user can access homepage
        $this->withSession($userSession)->get(route('index'))->assertOk();

        // Session B: Admin locks user account
        $adminResponse = $this->withSession(['admin_id' => 1])
            ->post(route('admin_user_lock'), [
                'user_id' => $this->staffUser->id,
            ]);
        $adminResponse->assertJson(['status' => true, 'message' => 'Khóa tài khoản thành công.']);

        // Back to Session A: User makes next request
        $blockedResponse = $this->withSession($userSession)->get(route('index'));
        $blockedResponse->assertStatus(302);
        $blockedResponse->assertRedirect(route('login'));
        $blockedResponse->assertSessionHas('error', 'Tài khoản của bạn đã bị khóa hoặc không còn hoạt động.');
    }

    /**
     * Test Case 7: Other roles and users remain unaffected when permissions are modified.
     */
    public function test_other_roles_permissions_remain_unaffected(): void
    {
        // Supervisor has customer upload permission
        $supervisorSession = $this->makeUserSession($this->supervisorUser);
        $this->assertContains('data.customers.upload', $supervisorSession['user_permissions']);

        // Admin modifies staff role
        $currentStaffPerms = $this->staffRole->permissions()->pluck('permissions.id')->toArray();
        $this->withSession(['admin_id' => 1])
            ->post(route('admin.roles.matrix', $this->staffRole->id), [
                'permission_ids' => array_values(array_unique(array_merge($currentStaffPerms, [$this->scanPerm->id]))),
            ]);

        // Supervisor's permissions are intact
        $supervisorResponse = $this->withSession($supervisorSession)->get(route('view_data_customer'));
        $supervisorResponse->assertOk();
        $supervisorResponse->assertSee('Đăng tải dữ liệu Excel');
    }
}
