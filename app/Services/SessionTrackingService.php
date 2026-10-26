<?php

namespace App\Services;

use App\Models\LoginSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SessionTrackingService
{
    protected static array $memoizedTableExists = [];
    protected static array $memoizedSessionStatus = [];

    public static function hasTable(string $table): bool
    {
        $conn = config('database.default', 'mysql');
        $key = $conn . ':' . $table;
        if (!isset(static::$memoizedTableExists[$key])) {
            static::$memoizedTableExists[$key] = Schema::connection($conn)->hasTable($table);
        }
        return static::$memoizedTableExists[$key];
    }

    public static function clearMemoizedTableCache(): void
    {
        static::$memoizedTableExists = [];
        static::$memoizedSessionStatus = [];
    }

    /**
     * Ghi nhận phiên đăng nhập mới khi user hoặc admin đăng nhập thành công.
     */
    public static function recordLogin($user, Request $request, string $authType = 'user'): ?LoginSession
    {
        try {
            if (!$request->hasSession()) {
                return null;
            }

            $sessionId = $request->session()->getId();
            $userId = is_object($user) ? $user->id : (int) $user;

            $lifetimeMinutes = (int) config('session.lifetime', 9000);
            $now = Carbon::now();
            $expiresAt = $now->copy()->addMinutes($lifetimeMinutes);

            $ua = (string) $request->userAgent();
            $ip = $request->ip();
            $deviceInfo = UserAgentParserService::parse($ua);

            // Cập nhật hoặc tạo mới bản ghi phiên
            $loginSession = LoginSession::updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'user_id' => $userId,
                    'ip_address' => $ip,
                    'user_agent' => $ua,
                    'device_type' => $deviceInfo['device_type'],
                    'browser' => $deviceInfo['browser'],
                    'platform' => $deviceInfo['platform'],
                    'login_at' => $now,
                    'last_activity_at' => $now,
                    'expires_at' => $expiresAt,
                    'status' => 'active',
                    'ended_at' => null,
                    'ended_reason' => null,
                    'ended_by_user_id' => null,
                ]
            );

            // Đồng bộ trực tiếp cột user_id trong bảng sessions của Laravel
            if (static::hasTable('sessions')) {
                DB::table('sessions')
                    ->where('id', $sessionId)
                    ->update(['user_id' => $userId]);
            }

            Cache::forget('session_revoked_' . $sessionId);
            unset(static::$memoizedSessionStatus[$sessionId]);
            Cache::put('session_status_' . $sessionId, 'active', 120);

            return $loginSession;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Lỗi recordLogin SessionTrackingService: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cập nhật thời điểm hoạt động gần nhất (thực hiện có throttle để tránh ghi DB liên tục)
     */
    public static function touch(string $sessionId, ?int $userId = null): void
    {
        if (empty($sessionId)) {
            return;
        }

        $cacheKey = 'session_touch_' . $sessionId;
        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            Cache::put($cacheKey, 1, 45); // Chặn ghi đè trong 45 giây

            $now = Carbon::now();
            $lifetimeMinutes = (int) config('session.lifetime', 9000);
            $expiresAt = $now->copy()->addMinutes($lifetimeMinutes);

            // 1. Cập nhật login_sessions
            $updateData = [
                'last_activity_at' => $now,
                'expires_at' => $expiresAt,
            ];
            if ($userId) {
                $updateData['user_id'] = $userId;
            }

            $affected = DB::table('login_sessions')
                ->where('session_id', $sessionId)
                ->where('status', 'active')
                ->update($updateData);

            // 2. Nếu phiên chưa từng tồn tại trong login_sessions nhưng có request từ người dùng
            if ($affected === 0 && $userId && static::hasTable('login_sessions')) {
                $exists = DB::table('login_sessions')->where('session_id', $sessionId)->exists();
                if (!$exists) {
                    $req = app()->bound('request') ? request() : null;
                    $ua = $req ? (string) $req->userAgent() : '';
                    $ip = $req ? $req->ip() : null;
                    $parsed = UserAgentParserService::parse($ua);

                    DB::table('login_sessions')->insert([
                        'session_id' => $sessionId,
                        'user_id' => $userId,
                        'ip_address' => $ip,
                        'user_agent' => $ua,
                        'device_type' => $parsed['device_type'],
                        'browser' => $parsed['browser'],
                        'platform' => $parsed['platform'],
                        'login_at' => $now,
                        'last_activity_at' => $now,
                        'expires_at' => $expiresAt,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // 3. Đồng bộ user_id sang bảng sessions của Laravel
            if ($userId && static::hasTable('sessions')) {
                DB::table('sessions')
                    ->where('id', $sessionId)
                    ->whereNull('user_id')
                    ->update(['user_id' => $userId]);
            }

            Cache::put('session_status_' . $sessionId, 'active', 120);
        } catch (\Throwable $e) {
            // Không làm gián đoạn luồng request
        }
    }

    /**
     * Ghi nhận khi người dùng chủ động đăng xuất
     */
    public static function recordLogout(string $sessionId, ?int $userId = null): void
    {
        if (empty($sessionId)) {
            return;
        }

        try {
            $now = Carbon::now();

            DB::table('login_sessions')
                ->where('session_id', $sessionId)
                ->where('status', 'active')
                ->update([
                    'status' => 'ended',
                    'ended_at' => $now,
                    'ended_reason' => 'logout',
                    'updated_at' => $now,
                ]);

            if (static::hasTable('sessions')) {
                DB::table('sessions')->where('id', $sessionId)->delete();
            }

            Cache::forget('session_status_' . $sessionId);
            Cache::forget('session_revoked_' . $sessionId);
            unset(static::$memoizedSessionStatus[$sessionId]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Lỗi recordLogout SessionTrackingService: ' . $e->getMessage());
        }
    }

    /**
     * Thu hồi (kết thúc) một phiên đã chọn bởi Quản trị viên
     */
    public static function revokeSession(int|string $identifier, int $adminUserId, string $reason = 'Thu hồi bởi Quản trị viên'): bool
    {
        try {
            $session = is_numeric($identifier)
                ? LoginSession::with('user')->find($identifier)
                : LoginSession::with('user')->where('session_id', $identifier)->first();

            if (!$session) {
                return false;
            }

            $admin = User::find($adminUserId);
            $now = Carbon::now();

            // 1. Cập nhật bản ghi phiên trong login_sessions
            $session->update([
                'status' => 'revoked',
                'ended_at' => $now,
                'ended_reason' => 'admin_revoked',
                'ended_by_user_id' => $adminUserId,
            ]);

            // 2. Xóa phiên khỏi bảng sessions của Laravel để hủy ngay tức thì ở request sau
            if (static::hasTable('sessions')) {
                DB::table('sessions')->where('id', $session->session_id)->delete();
            }

            // 3. Đánh dấu cờ revoked vào cache để kiểm tra siêu nhanh ở middleware
            Cache::forget('session_status_' . $session->session_id);
            Cache::put('session_revoked_' . $session->session_id, true, 86400);

            // 4. Ghi Audit Log
            ActivityLogService::logSession(
                'session.terminate',
                $session->user,
                $session->session_id,
                $admin,
                "Quản trị viên {$admin->name} đã kết thúc phiên đăng nhập (ID: {$session->session_id}) của người dùng " . ($session->user ? $session->user->name : "ID #{$session->user_id}") . ". Lý do: {$reason}",
                [
                    'session_db_id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'browser' => $session->browser,
                    'platform' => $session->platform,
                    'reason' => $reason,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Lỗi revokeSession SessionTrackingService: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Thu hồi tất cả phiên đang hoạt động của một người dùng
     */
    public static function revokeAllUserSessions(int $userId, int $adminUserId, string $reason = 'Thu hồi tất cả phiên bởi Quản trị viên'): int
    {
        try {
            $targetUser = User::find($userId);
            if (!$targetUser) {
                return 0;
            }

            $admin = User::find($adminUserId);
            $now = Carbon::now();

            // Lấy danh sách các session_id đang active của user
            $activeSessions = LoginSession::where('user_id', $userId)
                ->where('status', 'active')
                ->get();

            $sessionIds = $activeSessions->pluck('session_id')->toArray();
            $count = count($sessionIds);

            if ($count === 0) {
                // Kiểm tra thêm trong bảng sessions của Laravel nếu có sót
                if (static::hasTable('sessions')) {
                    $fallbackDeleted = DB::table('sessions')->where('user_id', $userId)->delete();
                    return $fallbackDeleted;
                }
                return 0;
            }

            // 1. Cập nhật tất cả bản ghi sang revoked
            LoginSession::whereIn('id', $activeSessions->pluck('id'))->update([
                'status' => 'revoked',
                'ended_at' => $now,
                'ended_reason' => 'admin_revoked_all',
                'ended_by_user_id' => $adminUserId,
            ]);

            // 2. Xóa tất cả phiên khỏi bảng sessions
            if (static::hasTable('sessions')) {
                DB::table('sessions')->whereIn('id', $sessionIds)->delete();
                DB::table('sessions')->where('user_id', $userId)->delete();
            }

            // 3. Cache cờ revoked cho từng session
            foreach ($sessionIds as $sid) {
                Cache::forget('session_status_' . $sid);
                Cache::put('session_revoked_' . $sid, true, 86400);
            }

            // 4. Ghi Audit Log
            ActivityLogService::logSession(
                'session.terminate_all',
                $targetUser,
                implode(', ', array_slice($sessionIds, 0, 5)),
                $admin,
                "Quản trị viên {$admin->name} đã kết thúc toàn bộ {$count} phiên đăng nhập của người dùng {$targetUser->name}. Lý do: {$reason}",
                [
                    'revoked_count' => $count,
                    'session_ids' => $sessionIds,
                    'reason' => $reason,
                ]
            );

            return $count;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Lỗi revokeAllUserSessions: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Xóa hoàn toàn một bản ghi phiên đăng nhập khỏi hệ thống
     */
    public static function deleteSession(int|string $identifier, int $adminUserId): bool
    {
        try {
            $session = is_numeric($identifier)
                ? LoginSession::with('user')->find($identifier)
                : LoginSession::with('user')->where('session_id', $identifier)->first();

            if (!$session) {
                return false;
            }

            $admin = User::find($adminUserId);
            $sid = $session->session_id;
            $user = $session->user;

            // Nếu phiên vẫn đang active trong bảng sessions của Laravel, xóa luôn
            if (static::hasTable('sessions')) {
                DB::table('sessions')->where('id', $sid)->delete();
            }

            // Xóa sạch cache
            Cache::forget('session_status_' . $sid);
            Cache::forget('session_revoked_' . $sid);
            unset(static::$memoizedSessionStatus[$sid]);

            // Ghi Audit Log trước khi xóa bản ghi
            ActivityLogService::logSession(
                'session.delete',
                $user,
                $sid,
                $admin,
                "Quản trị viên " . ($admin?->name ?? 'Admin') . " đã xóa bản ghi phiên đăng nhập (ID: {$sid}) của " . ($user ? $user->name : "Người dùng #{$session->user_id}"),
                [
                    'deleted_session_id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'browser' => $session->browser,
                    'platform' => $session->platform,
                    'status' => $session->status,
                ]
            );

            // Xóa bản ghi khỏi login_sessions
            $session->delete();

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Lỗi deleteSession: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Dọn dẹp hàng loạt các phiên cũ (đã kết thúc, thu hồi, hết hạn hoặc phiên test không còn trong sessions)
     */
    public static function cleanInactiveSessions(int $adminUserId, int $days = 0): int
    {
        try {
            $admin = User::find($adminUserId);

            // 1. Xóa các phiên có trạng thái ended, revoked, expired
            $query = DB::table('login_sessions')->whereIn('status', ['ended', 'revoked', 'expired']);
            if ($days > 0) {
                $query->where('updated_at', '<=', Carbon::now()->subDays($days));
            }
            $deletedCount = $query->delete();

            // 2. Xóa các phiên phantom (status = active nhưng không tồn tại trong bảng sessions của Laravel)
            if (static::hasTable('sessions')) {
                $activeSessionIds = DB::table('sessions')->pluck('id')->toArray();
                if (!empty($activeSessionIds)) {
                    $deletedPhantoms = DB::table('login_sessions')
                        ->where('status', 'active')
                        ->whereNotIn('session_id', $activeSessionIds)
                        ->delete();
                    $deletedCount += $deletedPhantoms;
                }
            }

            // Ghi Audit Log
            ActivityLogService::logSession(
                'session.clean_batch',
                null,
                'batch_cleanup',
                $admin,
                "Quản trị viên " . ($admin?->name ?? 'Admin') . " đã thực hiện dọn dẹp hàng loạt {$deletedCount} phiên đăng nhập cũ / không còn hoạt động",
                [
                    'deleted_count' => $deletedCount,
                    'days_threshold' => $days,
                ]
            );

            return $deletedCount;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Lỗi cleanInactiveSessions: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Kiểm tra xem một session_id đã bị thu hồi chưa
     */
    public static function isSessionRevoked(string $sessionId): bool
    {
        if (empty($sessionId)) {
            return false;
        }

        if (isset(static::$memoizedSessionStatus[$sessionId])) {
            return static::$memoizedSessionStatus[$sessionId] === 'revoked';
        }

        try {
            // 1. Kiểm tra nhanh cờ đã bị thu hồi qua cache
            if (Cache::get('session_revoked_' . $sessionId)) {
                static::$memoizedSessionStatus[$sessionId] = 'revoked';
                return true;
            }

            // 2. Kiểm tra nhanh cờ phiên đang hoạt động hợp lệ qua cache (tránh query DB)
            if (Cache::get('session_status_' . $sessionId) === 'active') {
                static::$memoizedSessionStatus[$sessionId] = 'active';
                return false;
            }

            // 3. Kiểm tra trong DB nếu cache chưa có
            if (static::hasTable('login_sessions')) {
                $status = DB::table('login_sessions')
                    ->where('session_id', $sessionId)
                    ->value('status');

                if ($status === 'revoked') {
                    static::$memoizedSessionStatus[$sessionId] = 'revoked';
                    Cache::forget('session_status_' . $sessionId);
                    Cache::put('session_revoked_' . $sessionId, true, 86400);
                    return true;
                }

                if ($status === 'active') {
                    static::$memoizedSessionStatus[$sessionId] = 'active';
                    Cache::put('session_status_' . $sessionId, 'active', 120);
                    return false;
                }
            }
        } catch (\Throwable $e) {
            // Trường hợp môi trường test in-memory không có bảng login_sessions
            return false;
        }

        static::$memoizedSessionStatus[$sessionId] = 'active';
        return false;
    }

    /**
     * Lấy số liệu thống kê phiên
     */
    public static function getStats(): array
    {
        $now = Carbon::now();

        $total = LoginSession::count();

        $active = LoginSession::where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->count();

        $activeUsers = LoginSession::where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $revokedOrEnded = LoginSession::whereIn('status', ['revoked', 'ended'])->count();

        $expired = LoginSession::where(function ($q) use ($now) {
            $q->where('status', 'expired')
                ->orWhere(function ($sub) use ($now) {
                    $sub->where('status', 'active')
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', $now);
                });
        })->count();

        return [
            'total' => $total,
            'active' => $active,
            'active_users' => $activeUsers,
            'revoked_or_ended' => $revokedOrEnded,
            'expired' => $expired,
        ];
    }
}
