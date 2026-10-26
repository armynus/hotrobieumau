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
        // 1. Tạo bảng login_sessions theo dõi phiên đăng nhập
        if (!Schema::hasTable('login_sessions')) {
            Schema::create('login_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 255)->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('device_type', 50)->nullable()->default('unknown'); // desktop, mobile, tablet, bot, unknown
                $table->string('browser', 100)->nullable(); // Chrome 120, Firefox, Safari...
                $table->string('platform', 100)->nullable(); // Windows 11, macOS, Android, iOS...
                $table->timestamp('login_at')->nullable()->index();
                $table->timestamp('last_activity_at')->nullable()->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->string('status', 20)->default('active')->index(); // active, expired, revoked, ended
                $table->timestamp('ended_at')->nullable();
                $table->string('ended_reason', 100)->nullable(); // logout, admin_revoked, admin_revoked_all, expired
                $table->unsignedBigInteger('ended_by_user_id')->nullable()->index();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('ended_by_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 2. Tạo bảng activity_logs ghi nhận nhật ký hoạt động
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name', 150)->nullable();
                $table->string('user_email', 150)->nullable();
                $table->string('action', 100)->index(); // auth.login.success, session.terminate, user.create...
                $table->string('action_label', 200); // Tên hiển thị tiếng Việt
                $table->string('module', 50)->index(); // auth, sessions, users, roles, branches, forms...
                $table->string('module_name', 100); // Quản lý phiên, Xác thực, Người dùng...
                $table->string('target_type', 100)->nullable(); // User, Role, Session, Branch...
                $table->string('target_id', 100)->nullable()->index();
                $table->string('target_label', 255)->nullable();
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('browser', 100)->nullable();
                $table->string('platform', 100)->nullable();
                $table->string('device_type', 50)->nullable()->default('unknown');
                $table->string('status', 20)->default('success')->index(); // success, failed, warning
                $table->longText('details')->nullable(); // Dữ liệu JSON mô tả chi tiết thao tác
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index('created_at');
            });
        }

        // 3. Đăng ký quyền mới vào hệ thống RBAC
        $this->seedNewPermissions();

        // 4. Đồng bộ dữ liệu phiên hiện hữu trong bảng sessions sang login_sessions (không làm mất phiên đang hoạt động)
        $this->backfillExistingSessions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa quyền đã tạo
        if (Schema::hasTable('permissions')) {
            $permIds = DB::table('permissions')
                ->whereIn('slug', ['system.sessions.view', 'system.sessions.manage', 'system.audit_logs.view'])
                ->pluck('id')
                ->toArray();

            if (!empty($permIds)) {
                if (Schema::hasTable('role_permission')) {
                    DB::table('role_permission')->whereIn('permission_id', $permIds)->delete();
                }
                DB::table('permissions')->whereIn('id', $permIds)->delete();
            }
        }

        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('login_sessions');
    }

    private function seedNewPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $newPerms = [
            [
                'name' => 'Xem & theo dõi phiên đăng nhập',
                'slug' => 'system.sessions.view',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Xem danh sách, chi tiết phiên đăng nhập, IP, thiết bị và trạng thái phiên người dùng',
                'sort_order' => 47,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Quản lý & thu hồi phiên đăng nhập',
                'slug' => 'system.sessions.manage',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Quyền kết thúc (thu hồi) phiên đăng nhập đơn lẻ hoặc tất cả phiên của người dùng',
                'sort_order' => 48,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Tra cứu nhật ký hoạt động hệ thống',
                'slug' => 'system.audit_logs.view',
                'module' => 'system',
                'module_name' => 'Quản trị Hệ thống',
                'description' => 'Xem và tra cứu nhật ký đăng nhập, thao tác quản trị và thay đổi dữ liệu của người dùng',
                'sort_order' => 49,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($newPerms as $p) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $p['slug']],
                $p
            );
        }

        // Cấp quyền cho vai trò Admin
        if (Schema::hasTable('roles') && Schema::hasTable('role_permission')) {
            $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
            if ($adminRoleId) {
                $permIds = DB::table('permissions')
                    ->whereIn('slug', ['system.sessions.view', 'system.sessions.manage', 'system.audit_logs.view'])
                    ->pluck('id')
                    ->toArray();

                foreach ($permIds as $permId) {
                    DB::table('role_permission')->updateOrInsert([
                        'role_id' => $adminRoleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        if (class_exists(\App\Models\Role::class)) {
            \App\Models\Role::bumpPermissionsVersion();
        }
    }

    private function backfillExistingSessions(): void
    {
        if (!Schema::hasTable('sessions') || !Schema::hasTable('login_sessions')) {
            return;
        }

        $sessionLifetimeMinutes = (int) config('session.lifetime', 9000);
        $sessionLifetimeSeconds = $sessionLifetimeMinutes * 60;
        $currentTime = time();

        $rows = DB::table('sessions')->get();
        foreach ($rows as $row) {
            $userId = $row->user_id;

            // Nếu user_id ở cột chưa có, thử giải mã payload
            if (!$userId && !empty($row->payload)) {
                try {
                    $payload = @unserialize(base64_decode($row->payload));
                    if (is_array($payload)) {
                        $userId = $payload['user_id'] ?? $payload['admin_id'] ?? null;
                    }
                } catch (\Throwable $e) {
                    $userId = null;
                }

                if ($userId) {
                    // Đồng bộ lại cột user_id trong bảng sessions
                    DB::table('sessions')->where('id', $row->id)->update(['user_id' => $userId]);
                }
            }

            // Chỉ đồng bộ các phiên có gắn với tài khoản người dùng/admin
            if ($userId) {
                // Kiểm tra xem đã có trong login_sessions chưa
                $exists = DB::table('login_sessions')->where('session_id', $row->id)->exists();
                if (!$exists) {
                    $lastActivity = (int) $row->last_activity;
                    $isExpired = ($currentTime - $lastActivity) > $sessionLifetimeSeconds;
                    $loginAt = date('Y-m-d H:i:s', $lastActivity);
                    $lastActivityAt = date('Y-m-d H:i:s', $lastActivity);
                    $expiresAt = date('Y-m-d H:i:s', $lastActivity + $sessionLifetimeSeconds);

                    // Phân tích sơ bộ User-Agent
                    $ua = (string) $row->user_agent;
                    $deviceInfo = $this->parseUserAgentSummary($ua);

                    DB::table('login_sessions')->insert([
                        'session_id' => $row->id,
                        'user_id' => $userId,
                        'ip_address' => $row->ip_address,
                        'user_agent' => $ua,
                        'device_type' => $deviceInfo['device_type'],
                        'browser' => $deviceInfo['browser'],
                        'platform' => $deviceInfo['platform'],
                        'login_at' => $loginAt,
                        'last_activity_at' => $lastActivityAt,
                        'expires_at' => $expiresAt,
                        'status' => $isExpired ? 'expired' : 'active',
                        'created_at' => $loginAt,
                        'updated_at' => $lastActivityAt,
                    ]);
                }
            }
        }
    }

    private function parseUserAgentSummary(string $ua): array
    {
        $deviceType = 'desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $deviceType = 'tablet';
        } elseif (preg_match('/(mobi|ipod|iphone|blackberry|opera mini|iemobile)/i', $ua)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/curl|bot|crawl|slurp|spider|mediapartners|symfony/i', $ua)) {
            $deviceType = 'bot';
        }

        // Platform
        $platform = 'Không xác định';
        if (preg_match('/windows nt 10/i', $ua)) $platform = 'Windows 10/11';
        elseif (preg_match('/windows nt 6\.3/i', $ua)) $platform = 'Windows 8.1';
        elseif (preg_match('/windows nt 6\.2/i', $ua)) $platform = 'Windows 8';
        elseif (preg_match('/windows nt 6\.1/i', $ua)) $platform = 'Windows 7';
        elseif (preg_match('/windows/i', $ua)) $platform = 'Windows';
        elseif (preg_match('/macintosh|mac os x/i', $ua)) $platform = 'macOS';
        elseif (preg_match('/iphone/i', $ua)) $platform = 'iOS (iPhone)';
        elseif (preg_match('/ipad/i', $ua)) $platform = 'iPadOS';
        elseif (preg_match('/android/i', $ua)) $platform = 'Android';
        elseif (preg_match('/linux/i', $ua)) $platform = 'Linux';

        // Browser
        $browser = 'Trình duyệt khác';
        if (preg_match('/edg\/([0-9\.]+)/i', $ua, $m)) $browser = 'Edge ' . explode('.', $m[1])[0];
        elseif (preg_match('/coc_coc_browser\/([0-9\.]+)/i', $ua, $m)) $browser = 'Cốc Cốc ' . explode('.', $m[1])[0];
        elseif (preg_match('/chrome\/([0-9\.]+)/i', $ua, $m)) $browser = 'Chrome ' . explode('.', $m[1])[0];
        elseif (preg_match('/firefox\/([0-9\.]+)/i', $ua, $m)) $browser = 'Firefox ' . explode('.', $m[1])[0];
        elseif (preg_match('/safari\/([0-9\.]+)/i', $ua, $m) && !preg_match('/chrome/i', $ua)) $browser = 'Safari';
        elseif (preg_match('/curl/i', $ua)) $browser = 'cURL';
        elseif (preg_match('/symfony/i', $ua)) $browser = 'Symfony';

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
        ];
    }
};
