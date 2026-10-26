<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branches;
use App\Models\LoginSession;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\SessionTrackingService;
use App\Services\UserAgentParserService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSessionAndAuditLogTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $regularUser;
    protected Branches $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branches::first() ?? Branches::create([
            'branch_name' => 'Chi nhánh Test Audit',
            'branch_code' => '9988',
            'branch_type' => 'type_1',
            'status' => 'active',
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Test Audit',
            'email' => 'admin_test_' . uniqid() . '@agribank.com.vn',
            'password' => bcrypt('123456'),
            'branch_id' => $this->branch->id,
            'role_id' => 0, // Admin
            'document_role' => 'admin',
            'status' => 'active',
        ]);

        $this->regularUser = User::create([
            'name' => 'User Test Audit',
            'email' => 'user_test_' . uniqid() . '@agribank.com.vn',
            'password' => bcrypt('123456'),
            'branch_id' => $this->branch->id,
            'role_id' => 2, // Staff
            'document_role' => 'user',
            'status' => 'active',
        ]);
    }

    /**
     * 1. Kiểm tra các quyền mới đã được đăng ký trong RBAC và gán cho vai trò Admin
     */
    public function test_new_permissions_registered_and_assigned_to_admin(): void
    {
        $sessionViewPerm = Permission::where('slug', 'system.sessions.view')->first();
        $sessionManagePerm = Permission::where('slug', 'system.sessions.manage')->first();
        $auditLogViewPerm = Permission::where('slug', 'system.audit_logs.view')->first();

        $this->assertNotNull($sessionViewPerm, 'Quyền system.sessions.view phải tồn tại');
        $this->assertNotNull($sessionManagePerm, 'Quyền system.sessions.manage phải tồn tại');
        $this->assertNotNull($auditLogViewPerm, 'Quyền system.audit_logs.view phải tồn tại');

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $adminPermSlugs = $adminRole->permissions()->pluck('slug')->toArray();
            $this->assertContains('system.sessions.view', $adminPermSlugs);
            $this->assertContains('system.sessions.manage', $adminPermSlugs);
            $this->assertContains('system.audit_logs.view', $adminPermSlugs);
        }

        // Admin user có role_id = 0 phải có các quyền này
        $this->assertTrue($this->adminUser->hasPermission('system.sessions.view'));
        $this->assertTrue($this->adminUser->hasPermission('system.sessions.manage'));
        $this->assertTrue($this->adminUser->hasPermission('system.audit_logs.view'));

        // Regular user không có các quyền này
        $this->assertFalse($this->regularUser->hasPermission('system.sessions.view'));
        $this->assertFalse($this->regularUser->hasPermission('system.sessions.manage'));
        $this->assertFalse($this->regularUser->hasPermission('system.audit_logs.view'));
    }

    /**
     * 2. Kiểm tra truy cập khi chưa đăng nhập bị chặn
     */
    public function test_unauthenticated_access_is_blocked(): void
    {
        // GET trang quản lý phiên
        $response = $this->get('/admin/sessions');
        $response->assertRedirect(route('login_admin'));

        // AJAX lấy dữ liệu phiên
        $responseJson = $this->getJson('/admin/sessions/data');
        $this->assertTrue($responseJson->isRedirect() || $responseJson->getStatusCode() === 401);

        // GET trang nhật ký
        $responseAudit = $this->get('/admin/audit-logs');
        $responseAudit->assertRedirect(route('login_admin'));

        // POST thu hồi phiên trái phép
        $responseRevoke = $this->postJson('/admin/sessions/99999/revoke');
        $this->assertTrue($responseRevoke->isRedirect() || in_array($responseRevoke->getStatusCode(), [401, 403, 404]));
    }

    /**
     * 3. Người dùng thường (USER) không có quyền admin bị chặn truy cập
     */
    public function test_regular_user_cannot_access_admin_monitoring_routes(): void
    {
        $response = $this->withSession([
            'user_id' => $this->regularUser->id,
            'user_role' => 2,
            'user_permissions' => ['forms.view', 'documents.view'],
        ])->get('/admin/sessions');

        // Phải bị chặn bởi AdminMiddleware (chuyển hướng login_admin)
        $response->assertRedirect(route('login_admin'));

        $responseAudit = $this->withSession([
            'user_id' => $this->regularUser->id,
            'user_role' => 2,
            'user_permissions' => ['forms.view', 'documents.view'],
        ])->get('/admin/audit-logs');

        $responseAudit->assertRedirect(route('login_admin'));
    }

    /**
     * 4. Quản trị viên có quyền xem được danh sách phiên và số liệu thống kê
     */
    public function test_admin_can_view_sessions_and_stats(): void
    {
        $response = $this->withSession([
            'admin_id' => $this->adminUser->id,
            'admin_name' => $this->adminUser->name,
            'admin_email' => $this->adminUser->email,
        ])->get('/admin/sessions');

        $response->assertStatus(200);
        $response->assertSee('Quản lý phiên đăng nhập');
        $response->assertSee('Phiên đang hoạt động');

        // AJAX DataTables
        $responseData = $this->withSession([
            'admin_id' => $this->adminUser->id,
            'admin_name' => $this->adminUser->name,
            'admin_email' => $this->adminUser->email,
        ])->getJson('/admin/sessions/data');

        $responseData->assertStatus(200);
        $responseData->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered', 'stats']);
    }

    /**
     * 5. Quản trị viên xem được danh sách nhật ký hoạt động
     */
    public function test_admin_can_view_audit_logs(): void
    {
        // Ghi 1 log mẫu
        ActivityLogService::logAuth('auth.login.success', $this->regularUser, null, 'Test đăng nhập thành công');

        $response = $this->withSession([
            'admin_id' => $this->adminUser->id,
            'admin_name' => $this->adminUser->name,
            'admin_email' => $this->adminUser->email,
        ])->get('/admin/audit-logs');

        $response->assertStatus(200);
        $response->assertSee('Nhật ký hoạt động hệ thống');

        $responseData = $this->withSession([
            'admin_id' => $this->adminUser->id,
            'admin_name' => $this->adminUser->name,
            'admin_email' => $this->adminUser->email,
        ])->getJson('/admin/audit-logs/data');

        $responseData->assertStatus(200);
        $responseData->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    }

    /**
     * 6. Kiểm tra thao tác thu hồi một phiên làm việc đơn lẻ (Revoke Single Session)
     * Thao tác phải xóa phiên trong bảng sessions và vô hiệu hóa ngay tức thì ở request sau.
     */
    public function test_admin_can_revoke_single_session_with_immediate_invalidation(): void
    {
        $testSessionId = Str::random(40);

        // 1. Giả lập phiên đang hoạt động trong bảng sessions của Laravel
        DB::table('sessions')->insert([
            'id' => $testSessionId,
            'user_id' => $this->regularUser->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
            'payload' => base64_encode(serialize(['user_id' => $this->regularUser->id])),
            'last_activity' => time(),
        ]);

        // 2. Tạo bản ghi trong login_sessions
        $loginSession = LoginSession::create([
            'session_id' => $testSessionId,
            'user_id' => $this->regularUser->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
            'device_type' => 'desktop',
            'browser' => 'Chrome 120',
            'platform' => 'Windows 10/11',
            'login_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addMinutes(9000),
            'status' => 'active',
        ]);

        $this->assertEquals('active', $loginSession->getEffectiveStatus());
        $this->assertTrue(DB::table('sessions')->where('id', $testSessionId)->exists());

        // 3. Admin thực hiện thu hồi phiên
        $response = $this->withSession([
            'admin_id' => $this->adminUser->id,
            'admin_name' => $this->adminUser->name,
            'admin_email' => $this->adminUser->email,
        ])->postJson("/admin/sessions/{$loginSession->id}/revoke", [
            'reason' => 'Kiểm tra bảo mật',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // 4. Kiểm tra trạng thái trong DB: login_sessions phải đổi sang 'revoked'
        $loginSession->refresh();
        $this->assertEquals('revoked', $loginSession->status);
        $this->assertEquals('admin_revoked', $loginSession->ended_reason);
        $this->assertEquals($this->adminUser->id, $loginSession->ended_by_user_id);
        $this->assertNotNull($loginSession->ended_at);

        // 5. Kiểm tra bảng sessions của Laravel: Phiên đã bị xóa khỏi sessions table
        $this->assertFalse(DB::table('sessions')->where('id', $testSessionId)->exists());

        // 6. Kiểm tra cờ thu hồi tức thời
        $this->assertTrue(SessionTrackingService::isSessionRevoked($testSessionId));

        // 7. Kiểm tra request tiếp theo của người dùng sở hữu phiên này
        $middleware = new \App\Http\Middleware\TenantDatabase();
        $userRequest = Request::create('/user', 'GET');
        $userRequest->headers->set('Accept', 'application/json');
        $userRequest->setLaravelSession(app('session.store'));
        $userRequest->session()->setId($testSessionId);
        $userRequest->session()->put('user_id', $this->regularUser->id);

        $resultResponse = $middleware->handle($userRequest, fn() => response('OK'));
        $this->assertEquals(401, $resultResponse->getStatusCode());

        // 8. Kiểm tra Audit Log đã ghi nhận hành động thu hồi
        $auditLog = ActivityLog::where('action', 'session.terminate')
            ->where('target_id', $testSessionId)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->adminUser->id, $auditLog->user_id);
    }

    /**
     * 7. Kiểm tra thu hồi tất cả phiên của một tài khoản (Revoke All Sessions)
     */
    public function test_admin_can_revoke_all_sessions_of_a_user(): void
    {
        $sid1 = Str::random(40);
        $sid2 = Str::random(40);

        foreach ([$sid1, $sid2] as $sid) {
            DB::table('sessions')->insert([
                'id' => $sid,
                'user_id' => $this->regularUser->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'payload' => base64_encode(serialize(['user_id' => $this->regularUser->id])),
                'last_activity' => time(),
            ]);

            LoginSession::create([
                'session_id' => $sid,
                'user_id' => $this->regularUser->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'status' => 'active',
                'login_at' => now(),
                'last_activity_at' => now(),
                'expires_at' => now()->addMinutes(9000),
            ]);
        }

        // Admin thu hồi tất cả phiên của regularUser
        $response = $this->withSession([
            'admin_id' => $this->adminUser->id,
            'admin_name' => $this->adminUser->name,
            'admin_email' => $this->adminUser->email,
        ])->postJson("/admin/sessions/revoke-all/{$this->regularUser->id}", [
            'reason' => 'Đổi mật khẩu bắt buộc',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Cả 2 phiên đều phải chuyển sang revoked và bị xóa khỏi sessions table
        $this->assertFalse(DB::table('sessions')->whereIn('id', [$sid1, $sid2])->exists());
        $revokedCount = LoginSession::where('user_id', $this->regularUser->id)
            ->where('status', 'revoked')
            ->whereIn('session_id', [$sid1, $sid2])
            ->count();
        $this->assertEquals(2, $revokedCount);

        // Kiểm tra audit log
        $auditLog = ActivityLog::where('action', 'session.terminate_all')
            ->where('user_id', $this->adminUser->id)
            ->first();
        $this->assertNotNull($auditLog);
    }

    /**
     * 8. Kiểm tra ActivityLogService tự động khử trùng (sanitize) dữ liệu nhạy cảm
     */
    public function test_activity_log_sanitizes_passwords_and_tokens(): void
    {
        $log = ActivityLogService::log([
            'user_id' => $this->adminUser->id,
            'action' => 'test.sensitive.action',
            'action_label' => 'Kiểm thử dữ liệu nhạy cảm',
            'module' => 'system',
            'module_name' => 'Hệ thống',
            'description' => 'Test sanitize',
            'details' => [
                'email' => 'admin@test.com',
                'password' => 'secret_password_123',
                'password_confirmation' => 'secret_password_123',
                '_token' => 'csrf_token_abc',
                'remember_token' => 'remember_xyz',
                'nested' => [
                    'old_password' => 'old_pass',
                    'normal_field' => 'giá trị bình thường',
                ],
            ],
        ]);

        $this->assertNotNull($log);
        $details = $log->details;

        $this->assertEquals('admin@test.com', $details['email']);
        $this->assertEquals('[REDACTED]', $details['password']);
        $this->assertEquals('[REDACTED]', $details['password_confirmation']);
        $this->assertEquals('[REDACTED]', $details['_token']);
        $this->assertEquals('[REDACTED]', $details['remember_token']);
        $this->assertEquals('[REDACTED]', $details['nested']['old_password']);
        $this->assertEquals('giá trị bình thường', $details['nested']['normal_field']);
    }

    /**
     * 9. Kiểm tra UserAgentParserService phân tích chính xác trình duyệt và hệ điều hành
     */
    public function test_user_agent_parser_extracts_real_device_info(): void
    {
        // Chrome trên Windows 11
        $uaWin = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $resWin = UserAgentParserService::parse($uaWin);
        $this->assertEquals('desktop', $resWin['device_type']);
        $this->assertStringContainsString('Windows 10/11', $resWin['platform']);
        $this->assertStringContainsString('Chrome 120', $resWin['browser']);

        // Safari trên iPhone iOS 17
        $uaIphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
        $resIphone = UserAgentParserService::parse($uaIphone);
        $this->assertEquals('mobile', $resIphone['device_type']);
        $this->assertStringContainsString('iOS', $resIphone['platform']);
        $this->assertStringContainsString('Safari', $resIphone['browser']);

        // Firefox trên Linux
        $uaLinux = 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0';
        $resLinux = UserAgentParserService::parse($uaLinux);
        $this->assertEquals('desktop', $resLinux['device_type']);
        $this->assertEquals('Linux', $resLinux['platform']);
        $this->assertStringContainsString('Firefox 115', $resLinux['browser']);

        // cURL
        $uaCurl = 'curl/7.68.0';
        $resCurl = UserAgentParserService::parse($uaCurl);
        $this->assertEquals('bot', $resCurl['device_type']);
        $this->assertStringContainsString('cURL', $resCurl['browser']);

        // Rỗng
        $resEmpty = UserAgentParserService::parse('');
        $this->assertEquals('unknown', $resEmpty['device_type']);
        $this->assertEquals('Không xác định', $resEmpty['browser']);
    }

    /**
     * 10. Người dùng thông thường đăng nhập thành công ghi nhận phiên đăng nhập nhưng KHÔNG ghi activity log
     */
    public function test_regular_user_login_does_not_create_activity_log_but_records_session(): void
    {
        $logCountBefore = ActivityLog::count();

        $response = $this->post(route('logins'), [
            'email' => $this->regularUser->email,
            'password' => '123456',
        ]);

        $response->assertRedirect(route('index'));

        // Phiên đăng nhập PHẢI được ghi nhận trong login_sessions
        $sessionRecord = LoginSession::where('user_id', $this->regularUser->id)
            ->where('status', 'active')
            ->first();
        $this->assertNotNull($sessionRecord);

        // KHÔNG ghi nhận log auth.login.success cho user thường
        $authLog = ActivityLog::where('user_id', $this->regularUser->id)
            ->where('action', 'auth.login.success')
            ->first();
        $this->assertNull($authLog);
    }

    /**
     * 11. Đăng nhập sai dưới 5 lần không ghi nhận bất kỳ log nào
     */
    public function test_failed_login_attempts_under_five_do_not_create_activity_log(): void
    {
        $logCountBefore = ActivityLog::count();

        // Thử đăng nhập sai 4 lần
        for ($i = 1; $i <= 4; $i++) {
            $response = $this->post(route('logins'), [
                'email' => $this->regularUser->email,
                'password' => 'wrong_password_' . $i,
            ]);
            $response->assertSessionHas('error');
        }

        $this->regularUser->refresh();
        $this->assertEquals(4, $this->regularUser->failed_login_attempts);
        $this->assertEquals('active', $this->regularUser->status);

        // Số lượng log không đổi (không ghi nhận từng lần sai)
        $this->assertEquals($logCountBefore, ActivityLog::count());
    }

    /**
     * 12. Đăng nhập sai lần thứ 5 làm khóa tài khoản và ghi đúng 1 sự kiện bảo mật auth.account.locked
     */
    public function test_fifth_failed_login_locks_account_and_creates_single_security_log(): void
    {
        $this->regularUser->update(['failed_login_attempts' => 4, 'status' => 'active']);

        $response = $this->post(route('logins'), [
            'email' => $this->regularUser->email,
            'password' => 'wrong_fifth_password',
        ]);

        $response->assertSessionHas('error');

        $this->regularUser->refresh();
        $this->assertEquals(5, $this->regularUser->failed_login_attempts);
        $this->assertEquals('inactive', $this->regularUser->status);

        // Phải ghi đúng 1 log khóa tài khoản
        $lockedLog = ActivityLog::where('user_id', $this->regularUser->id)
            ->where('action', 'auth.account.locked')
            ->first();

        $this->assertNotNull($lockedLog);
        $this->assertEquals('Tài khoản bị khóa tự động', $lockedLog->action_label);
        $this->assertEquals('failed', $lockedLog->status);
    }

    /**
     * 13. Người dùng đổi mật khẩu ghi nhận log auth.password.change và cập nhật password_changed_at
     */
    public function test_regular_user_password_change_creates_activity_log_and_updates_timestamp(): void
    {
        $response = $this->withSession([
            'user_id' => $this->regularUser->id,
            'user_role' => 2,
        ])->post(route('reset_password_user'), [
            'old_password' => '123456',
            'password' => 'new_secret_456',
            'repassword' => 'new_secret_456',
        ]);

        $response->assertSessionHas('message', 'Đổi mật khẩu thành công');

        $this->regularUser->refresh();
        $this->assertNotNull($this->regularUser->password_changed_at);
        $this->assertTrue(now()->diffInSeconds($this->regularUser->password_changed_at) < 60);

        // Phải có log auth.password.change
        $pwdLog = ActivityLog::where('user_id', $this->regularUser->id)
            ->where('action', 'auth.password.change')
            ->first();

        $this->assertNotNull($pwdLog);
        $this->assertEquals('Đổi mật khẩu tài khoản', $pwdLog->action_label);
        $this->assertEquals('success', $pwdLog->status);
    }

    /**
     * 14. Dữ liệu tài khoản phía Admin bao gồm trường password_changed_at
     */
    public function test_admin_user_data_and_detail_includes_password_changed_at(): void
    {
        $this->regularUser->update(['password_changed_at' => now()->subDays(2)]);

        $response = $this->withSession([
            'admin_id' => $this->adminUser->id,
        ])->getJson(route('admin_list_staff_data'));

        $response->assertOk();
        $data = $response->json('data');

        // Tìm regularUser trong data list
        $userRow = collect($data)->firstWhere('id', $this->regularUser->id);
        $this->assertNotNull($userRow);
        $this->assertArrayHasKey('password_changed_at', $userRow);
        $this->assertNotNull($userRow['password_changed_at']);
    }

    /**
     * 15. Tác vụ xóa log quá 5 năm (logs:prune-expired) xóa đúng các bản ghi cũ hơn 5 năm và giữ lại bản ghi mới
     */
    public function test_prune_expired_logs_command_deletes_records_older_than_five_years(): void
    {
        $sixYearsAgo = now()->subYears(6);
        $oneMonthAgo = now()->subDays(30);

        // Bản ghi log cũ 6 năm
        $oldLogId = DB::table('activity_logs')->insertGetId([
            'user_id' => $this->adminUser->id,
            'action' => 'test.old.action',
            'action_label' => 'Thao tác cũ 6 năm',
            'module' => 'system',
            'module_name' => 'Hệ thống',
            'status' => 'success',
            'created_at' => $sixYearsAgo,
            'updated_at' => $sixYearsAgo,
        ]);

        // Bản ghi log mới 1 tháng
        $newLogId = DB::table('activity_logs')->insertGetId([
            'user_id' => $this->adminUser->id,
            'action' => 'test.new.action',
            'action_label' => 'Thao tác mới 1 tháng',
            'module' => 'system',
            'module_name' => 'Hệ thống',
            'status' => 'success',
            'created_at' => $oneMonthAgo,
            'updated_at' => $oneMonthAgo,
        ]);

        // Bản ghi phiên đã kết thúc 6 năm trước
        $oldSessionId = 'ended_sess_' . Str::random(28);
        DB::table('login_sessions')->insert([
            'session_id' => $oldSessionId,
            'user_id' => $this->regularUser->id,
            'status' => 'ended',
            'login_at' => $sixYearsAgo,
            'created_at' => $sixYearsAgo,
            'updated_at' => $sixYearsAgo,
        ]);

        // Chạy lệnh artisan xóa log quá 5 năm
        $this->artisan('logs:prune-expired', ['--years' => 5])
            ->assertSuccessful();

        // Kiểm tra: log 6 năm bị xóa, log 1 tháng còn nguyên
        $this->assertFalse(DB::table('activity_logs')->where('id', $oldLogId)->exists());
        $this->assertTrue(DB::table('activity_logs')->where('id', $newLogId)->exists());

        // Phiên đã kết thúc 6 năm trước bị xóa
        $this->assertFalse(DB::table('login_sessions')->where('session_id', $oldSessionId)->exists());
    }

    /**
     * 16. Tác vụ xóa log TUYỆT ĐỐI KHÔNG XÓA phiên đang hoạt động (status = 'active')
     */
    public function test_prune_expired_logs_never_deletes_active_sessions(): void
    {
        $sixYearsAgo = now()->subYears(6);
        $activeOldSessionId = 'active_old_sess_' . Str::random(24);

        // Giả lập phiên đang active nhưng có thời gian tạo cũ
        DB::table('login_sessions')->insert([
            'session_id' => $activeOldSessionId,
            'user_id' => $this->regularUser->id,
            'status' => 'active',
            'login_at' => $sixYearsAgo,
            'created_at' => $sixYearsAgo,
            'updated_at' => $sixYearsAgo,
        ]);

        $this->artisan('logs:prune-expired', ['--years' => 5])
            ->assertSuccessful();

        // Phiên đang hoạt động PHẢI ĐƯỢC GIỮ NGUYÊN 100%
        $this->assertTrue(
            DB::table('login_sessions')->where('session_id', $activeOldSessionId)->exists()
        );
        $this->assertEquals(
            'active',
            DB::table('login_sessions')->where('session_id', $activeOldSessionId)->value('status')
        );
    }

    /**
     * 17. Admin có thể xóa 1 bản ghi phiên và tự động ghi Audit Log
     */
    public function test_admin_can_delete_single_session_record_and_creates_audit_log(): void
    {
        $sid = 'sess_delete_test_' . Str::random(20);
        $loginSession = LoginSession::create([
            'session_id' => $sid,
            'user_id' => $this->regularUser->id,
            'ip_address' => '127.0.0.1',
            'status' => 'ended',
            'login_at' => now()->subHours(2),
            'last_activity_at' => now()->subHour(),
        ]);

        $response = $this->withSession([
            'admin_id' => $this->adminUser->id,
            'admin_name' => $this->adminUser->name,
            'admin_email' => $this->adminUser->email,
        ])->deleteJson("/admin/sessions/{$loginSession->id}");

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Bản ghi phiên phải bị xóa khỏi DB
        $this->assertFalse(LoginSession::where('id', $loginSession->id)->exists());

        // Kiểm tra audit log
        $auditLog = ActivityLog::where('action', 'session.delete')
            ->where('target_id', $sid)
            ->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($this->adminUser->id, $auditLog->user_id);
    }

    /**
     * 18. Admin có thể dọn dẹp hàng loạt các phiên cũ (cleanInactiveSessions)
     */
    public function test_admin_can_clean_inactive_sessions_in_batch(): void
    {
        $sidEnded = 'sess_clean_ended_' . Str::random(20);
        $sidRevoked = 'sess_clean_revoked_' . Str::random(20);
        $sidActive = 'sess_clean_active_' . Str::random(20);

        LoginSession::create([
            'session_id' => $sidEnded,
            'user_id' => $this->regularUser->id,
            'status' => 'ended',
            'login_at' => now()->subDays(2),
        ]);
        LoginSession::create([
            'session_id' => $sidRevoked,
            'user_id' => $this->regularUser->id,
            'status' => 'revoked',
            'login_at' => now()->subDays(2),
        ]);

        // Tạo phiên active thực sự có trong bảng sessions
        DB::table('sessions')->insert([
            'id' => $sidActive,
            'user_id' => $this->regularUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => base64_encode(serialize(['user_id' => $this->regularUser->id])),
            'last_activity' => time(),
        ]);
        LoginSession::create([
            'session_id' => $sidActive,
            'user_id' => $this->regularUser->id,
            'status' => 'active',
            'login_at' => now(),
        ]);

        $response = $this->withSession([
            'admin_id' => $this->adminUser->id,
            'admin_name' => $this->adminUser->name,
            'admin_email' => $this->adminUser->email,
        ])->postJson('/admin/sessions/clean', ['days' => 0]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Phiên ended và revoked phải bị xóa
        $this->assertFalse(LoginSession::where('session_id', $sidEnded)->exists());
        $this->assertFalse(LoginSession::where('session_id', $sidRevoked)->exists());

        // Phiên active hợp lệ PHẢI ĐƯỢC GIỮ NGUYÊN
        $this->assertTrue(LoginSession::where('session_id', $sidActive)->exists());

        // Kiểm tra audit log
        $auditLog = ActivityLog::where('action', 'session.clean_batch')->first();
        $this->assertNotNull($auditLog);
    }

    /**
     * 19. Kiểm tra bộ lọc trạng thái ended_or_revoked trong DataTables
     */
    public function test_admin_can_filter_sessions_by_ended_or_revoked_status(): void
    {
        $sidActive = 'test_filter_act_' . Str::random(10);
        $sidEnded = 'test_filter_end_' . Str::random(10);

        LoginSession::create([
            'session_id' => $sidActive,
            'user_id' => $this->regularUser->id,
            'status' => 'active',
            'login_at' => now(),
        ]);
        LoginSession::create([
            'session_id' => $sidEnded,
            'user_id' => $this->regularUser->id,
            'status' => 'ended',
            'login_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);

        $response = $this->withSession([
            'admin_id' => $this->adminUser->id,
            'admin_name' => $this->adminUser->name,
            'admin_email' => $this->adminUser->email,
        ])->getJson('/admin/sessions/data?status=ended_or_revoked');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Kiểm tra kết quả trả về không chứa phiên active
        $statuses = collect($data)->pluck('status_badge')->implode(' ');
        $this->assertStringNotContainsString('Đang hoạt động', $statuses);
    }
}

