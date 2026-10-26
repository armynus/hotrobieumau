<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Str;

class ActivityLogService
{
    /**
     * Danh sách các key nhạy cảm cần lọc bỏ hoặc che giấu khỏi log
     */
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'old_password',
        'new_password',
        'repassword',
        '_token',
        'token',
        'remember_token',
        'authorization',
        'cookie',
        'secret',
        'api_key',
        'access_token',
        'private_key',
        'pin',
    ];

    /**
     * Ghi nhận một bản ghi nhật ký hoạt động
     *
     * @param  array  $data
     * @return ActivityLog|null
     */
    public static function log(array $data): ?ActivityLog
    {
        try {
            $req = app()->bound('request') ? request() : null;

            $ip = $data['ip_address'] ?? ($req ? $req->ip() : null);
            $ua = $data['user_agent'] ?? ($req ? $req->userAgent() : null);

            $parsedUa = UserAgentParserService::parse($ua);

            $userId = $data['user_id'] ?? null;
            $userName = $data['user_name'] ?? null;
            $userEmail = $data['user_email'] ?? null;

            if (!$userId && $req && $req->hasSession()) {
                $userId = $req->session()->get('user_id') ?? $req->session()->get('admin_id');
            }

            if ($userId && (!$userName || !$userEmail)) {
                $user = User::find($userId);
                if ($user) {
                    $userName = $userName ?: $user->name;
                    $userEmail = $userEmail ?: $user->email;
                }
            }

            // Làm sạch chi tiết
            $details = isset($data['details']) && is_array($data['details'])
                ? static::sanitize($data['details'])
                : ($data['details'] ?? null);

            return ActivityLog::create([
                'user_id' => $userId,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'action' => $data['action'] ?? 'system.action',
                'action_label' => $data['action_label'] ?? 'Thao tác hệ thống',
                'module' => $data['module'] ?? 'system',
                'module_name' => $data['module_name'] ?? 'Hệ thống',
                'target_type' => $data['target_type'] ?? null,
                'target_id' => isset($data['target_id']) ? (string) $data['target_id'] : null,
                'target_label' => $data['target_label'] ?? null,
                'description' => $data['description'] ?? null,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'browser' => $data['browser'] ?? $parsedUa['browser'],
                'platform' => $data['platform'] ?? $parsedUa['platform'],
                'device_type' => $data['device_type'] ?? $parsedUa['device_type'],
                'status' => $data['status'] ?? 'success',
                'details' => $details,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Lỗi ghi ActivityLog: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            return null;
        }
    }

    /**
     * Ghi log xác thực (Đăng nhập, đăng xuất, đổi mật khẩu)
     */
    public static function logAuth(
        string $action,
        $user,
        ?Request $request,
        string $description,
        array $details = [],
        string $status = 'success'
    ): ?ActivityLog {
        $labels = [
            'auth.login.success' => 'Đăng nhập thành công',
            'auth.login.failed' => 'Đăng nhập thất bại',
            'auth.logout' => 'Đăng xuất khỏi hệ thống',
            'auth.password.change' => 'Đổi mật khẩu tài khoản',
            'auth.account.locked' => 'Tài khoản bị khóa tự động',
        ];

        $actionLabel = $labels[$action] ?? 'Xác thực tài khoản';

        $userId = is_object($user) ? $user->id : (is_numeric($user) ? (int) $user : null);
        $userName = is_object($user) ? ($user->name ?? null) : null;
        $userEmail = is_object($user) ? ($user->email ?? null) : ($details['email'] ?? null);

        $ip = $request ? $request->ip() : null;
        $ua = $request ? $request->userAgent() : null;

        return static::log([
            'user_id' => $userId,
            'user_name' => $userName,
            'user_email' => $userEmail,
            'action' => $action,
            'action_label' => $actionLabel,
            'module' => 'auth',
            'module_name' => 'Xác thực & Bảo mật',
            'target_type' => 'User',
            'target_id' => $userId,
            'target_label' => $userName ?: $userEmail,
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'status' => $status,
            'details' => $details,
        ]);
    }

    /**
     * Ghi log kết thúc/thu hồi phiên đăng nhập
     */
    public static function logSession(
        string $action,
        $targetUser,
        string $sessionId,
        $actorUser,
        string $description,
        array $details = []
    ): ?ActivityLog {
        $actionLabels = [
            'session.terminate' => 'Thu hồi phiên đăng nhập',
            'session.terminate_all' => 'Thu hồi tất cả phiên của người dùng',
            'session.delete' => 'Xóa bản ghi phiên đăng nhập',
            'session.clean_batch' => 'Dọn dẹp hàng loạt phiên cũ',
        ];

        $actorId = is_object($actorUser) ? $actorUser->id : (is_numeric($actorUser) ? (int) $actorUser : null);
        $actorName = is_object($actorUser) ? ($actorUser->name ?? null) : null;
        $actorEmail = is_object($actorUser) ? ($actorUser->email ?? null) : null;

        $targetId = is_object($targetUser) ? $targetUser->id : (is_numeric($targetUser) ? (int) $targetUser : null);
        $targetName = is_object($targetUser) ? ($targetUser->name ?? null) : null;

        return static::log([
            'user_id' => $actorId,
            'user_name' => $actorName,
            'user_email' => $actorEmail,
            'action' => $action,
            'action_label' => $actionLabels[$action] ?? 'Quản lý phiên',
            'module' => 'sessions',
            'module_name' => 'Quản lý Phiên đăng nhập',
            'target_type' => 'Session',
            'target_id' => $sessionId,
            'target_label' => $targetName ? "Tài khoản: {$targetName} (ID #{$targetId})" : "Phiên: " . Str::limit($sessionId, 12),
            'description' => $description,
            'status' => 'warning',
            'details' => array_merge([
                'session_id' => $sessionId,
                'target_user_id' => $targetId,
                'target_user_name' => $targetName,
            ], $details),
        ]);
    }

    /**
     * Ghi log nghiệp vụ hệ thống (CRUD người dùng, vai trò, chi nhánh, biểu mẫu...)
     */
    public static function logBusiness(
        string $module,
        string $action,
        ?string $targetType,
        ?string $targetId,
        ?string $targetLabel,
        string $description,
        array $details = [],
        ?User $actor = null,
        string $status = 'success'
    ): ?ActivityLog {
        $moduleNames = [
            'users' => 'Quản lý Người dùng',
            'roles' => 'Vai trò & Phân quyền',
            'branches' => 'Quản lý Chi nhánh',
            'departments' => 'Quản lý Phòng ban',
            'transaction_offices' => 'Phòng giao dịch',
            'positions' => 'Quản lý Chức vụ',
            'forms' => 'Biểu mẫu Nghiệp vụ',
            'it_support' => 'Hỗ trợ IT',
            'documents' => 'Quản lý Văn bản',
            'system' => 'Quản trị Hệ thống',
        ];

        $moduleName = $moduleNames[$module] ?? Str::title(str_replace('_', ' ', $module));

        $actionLabels = [
            'create' => 'Thêm mới',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
            'lock' => 'Khóa',
            'unlock' => 'Mở khóa',
            'matrix.update' => 'Cập nhật ma trận quyền',
            'status.update' => 'Cập nhật trạng thái',
        ];

        $subAction = explode('.', $action)[1] ?? $action;
        $actionLabel = ($actionLabels[$subAction] ?? 'Thao tác') . ' ' . strtolower($targetType ?: 'dữ liệu');

        $actorId = $actor ? $actor->id : (session('user_id') ?? session('admin_id'));
        $actorName = $actor ? $actor->name : (session('user_name') ?? session('admin_name'));
        $actorEmail = $actor ? $actor->email : (session('user_email') ?? session('admin_email'));

        return static::log([
            'user_id' => $actorId,
            'user_name' => $actorName,
            'user_email' => $actorEmail,
            'action' => $action,
            'action_label' => $actionLabel,
            'module' => $module,
            'module_name' => $moduleName,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_label' => $targetLabel,
            'description' => $description,
            'status' => $status,
            'details' => $details,
        ]);
    }

    /**
     * Khử trùng và loại bỏ các trường nhạy cảm khỏi mảng dữ liệu log
     */
    public static function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;
            foreach (static::$sensitiveKeys as $pattern) {
                if ($lowerKey === $pattern || str_contains($lowerKey, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = static::sanitize($value);
            } elseif (is_string($value) && mb_strlen($value) > 2000) {
                // Rút gọn các chuỗi văn bản quá dài để tránh phình database
                $sanitized[$key] = Str::limit($value, 2000, '... [dữ liệu quá dài đã được rút gọn]');
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Tự động xóa các bản ghi nhật ký hoạt động và lịch sử phiên đã hết hạn/thu hồi quá số năm quy định (mặc định 5 năm)
     * TUYỆT ĐỐI KHÔNG XÓA phiên đang hoạt động (status = 'active')
     *
     * @param int $years
     * @return array{logs: int, sessions: int}
     */
    public static function pruneExpiredLogs(int $years = 5): array
    {
        $cutoff = \Carbon\Carbon::now()->subYears($years);
        $deletedLogs = 0;
        $deletedSessions = 0;

        // 1. Xóa activity_logs cũ hơn $cutoff
        if (\Illuminate\Support\Facades\Schema::hasTable('activity_logs')) {
            $deletedLogs = \Illuminate\Support\Facades\DB::table('activity_logs')
                ->where('created_at', '<=', $cutoff)
                ->delete();
        }

        // 2. Xóa login_sessions cũ hơn $cutoff có trạng thái đã kết thúc/hết hạn/thu hồi
        // TUYỆT ĐỐI không xóa phiên có status = 'active'
        if (\Illuminate\Support\Facades\Schema::hasTable('login_sessions')) {
            $deletedSessions = \Illuminate\Support\Facades\DB::table('login_sessions')
                ->whereIn('status', ['ended', 'revoked', 'expired'])
                ->where('created_at', '<=', $cutoff)
                ->delete();
        }

        return [
            'logs' => $deletedLogs,
            'sessions' => $deletedSessions,
        ];
    }
}
