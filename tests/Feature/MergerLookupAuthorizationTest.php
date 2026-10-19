<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MergerLookupAuthorizationTest extends TestCase
{
    private User $staffUser;
    private User $supervisorUser;
    private Permission $mergerPerm;

    protected function setUp(): void
    {
        parent::setUp();

        // Retrieve permission
        $this->mergerPerm = Permission::where('slug', 'data.merger_lookup')->firstOrFail();
        $this->mergerPerm->update(['status' => 'active']);
        Cache::forget('perm_active_data.merger_lookup');

        // Ensure roles
        $staffRole = Role::where('slug', 'staff')->firstOrFail();
        $supervisorRole = Role::where('slug', 'supervisor')->firstOrFail();

        // Staff does NOT have data.merger_lookup
        $staffRole->permissions()->detach($this->mergerPerm->id);

        // Supervisor DOES have data.merger_lookup
        if (!$supervisorRole->permissions()->where('slug', 'data.merger_lookup')->exists()) {
            $supervisorRole->permissions()->attach($this->mergerPerm->id);
        }

        // Test staff user
        $this->staffUser = User::firstOrCreate(
            ['email' => 'test_staff_merger@bidv.com.vn'],
            [
                'name' => 'Nhân viên Test Merger',
                'password' => bcrypt('password'),
                'role_id' => 2,
                'status' => 'active',
            ]
        );
        $this->staffUser->roles()->sync([$staffRole->id]);

        // Test supervisor user
        $this->supervisorUser = User::firstOrCreate(
            ['email' => 'test_supervisor_merger@bidv.com.vn'],
            [
                'name' => 'Kiểm soát viên Test Merger',
                'password' => bcrypt('password'),
                'role_id' => 1,
                'status' => 'active',
            ]
        );
        $this->supervisorUser->roles()->sync([$supervisorRole->id]);

        Role::bumpPermissionsVersion();
    }

    public function test_staff_without_permission_cannot_access_merger_lookup(): void
    {
        $this->withSession([
            'user_id' => $this->staffUser->id,
            'user_role' => $this->staffUser->role_id,
            'user_permissions' => $this->staffUser->getAllPermissionSlugs(),
            'user_permissions_version' => Role::permissionsVersion(),
        ]);

        // 1. Helper check
        $this->assertFalse(auth_user_can('data.merger_lookup'));

        // 2. Direct web navigation blocked and redirects to index
        $response = $this->get('/merger_lookup');
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
        $response->assertSessionHas('error');

        // 3. AJAX options endpoint blocked with 403 JSON
        $ajaxResponse = $this->getJson('/merger_lookup/options?kind=new_province');
        $ajaxResponse->assertStatus(403);
        $ajaxResponse->assertJsonPath('status', false);

        // 4. Detail endpoint blocked with 403 JSON
        $detailResponse = $this->getJson('/merger_lookup/detail?kind=new_ward&id=1');
        $detailResponse->assertStatus(403);

        // 5. Sidebar view does not contain merger lookup link
        $this->view('user.layouts.sidebar')->assertDontSee(route('merger_lookup'));
    }

    public function test_supervisor_with_permission_can_access_when_feature_active(): void
    {
        $this->withSession([
            'user_id' => $this->supervisorUser->id,
            'user_role' => $this->supervisorUser->role_id,
            'user_permissions' => $this->supervisorUser->getAllPermissionSlugs(),
            'user_permissions_version' => Role::permissionsVersion(),
        ]);

        // 1. Helper check
        $this->assertTrue(auth_user_can('data.merger_lookup'));

        // 2. Direct web navigation allowed
        $response = $this->get('/merger_lookup');
        $response->assertOk();
        $response->assertSee('Tra cứu');

        // 3. Sidebar view contains merger lookup link
        $this->view('user.layouts.sidebar')->assertSee(route('merger_lookup'));
    }

    public function test_when_feature_toggled_inactive_all_users_are_blocked(): void
    {
        // Toggle feature status to inactive
        $this->mergerPerm->update(['status' => 'inactive']);
        Cache::forget('perm_active_data.merger_lookup');
        Role::bumpPermissionsVersion();

        // Even though supervisor has this permission assigned in their role:
        $this->withSession([
            'user_id' => $this->supervisorUser->id,
            'user_role' => $this->supervisorUser->role_id,
            'user_permissions' => $this->supervisorUser->getAllPermissionSlugs(),
            'user_permissions_version' => Role::permissionsVersion(),
        ]);

        // 1. Helper check returns false immediately
        $this->assertFalse(auth_user_can('data.merger_lookup'));

        // 2. Direct web navigation is blocked
        $response = $this->get('/merger_lookup');
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));

        // 3. AJAX endpoint blocked with 403 JSON
        $ajaxResponse = $this->getJson('/merger_lookup/options?kind=new_province');
        $ajaxResponse->assertStatus(403);

        // 4. Sidebar view does not show the feature link
        $this->view('user.layouts.sidebar')->assertDontSee(route('merger_lookup'));

        // Restore active state
        $this->mergerPerm->update(['status' => 'active']);
        Cache::forget('perm_active_data.merger_lookup');
        Role::bumpPermissionsVersion();
    }

    public function test_active_user_session_refreshes_automatically_when_permission_version_bumps(): void
    {
        // Simulate a logged-in user whose session has stale permissions (e.g. data.merger_lookup was in session)
        $oldVersion = Role::permissionsVersion();
        
        $this->withSession([
            'user_id' => $this->staffUser->id,
            'user_role' => $this->staffUser->role_id,
            // Malicious or stale session claiming they have merger_lookup:
            'user_permissions' => ['forms.view', 'data.merger_lookup'],
            'user_permissions_version' => $oldVersion,
        ]);

        // Admin updates permissions matrix -> bumps permissions version
        Role::bumpPermissionsVersion();

        // Staff user makes a request to /merger_lookup
        // CheckPermission detects session version mismatch, reloads user's actual permissions from DB
        // Since staffUser does not have data.merger_lookup in DB, they are blocked!
        $response = $this->get('/merger_lookup');
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));

        // Verify session was refreshed and purged of data.merger_lookup
        $updatedPerms = session('user_permissions');
        $this->assertNotContains('data.merger_lookup', $updatedPerms);
        $this->assertEquals(Role::permissionsVersion(), session('user_permissions_version'));
    }
}
