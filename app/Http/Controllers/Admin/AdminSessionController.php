<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginSession;
use App\Models\User;
use App\Services\SessionTrackingService;
use App\Services\UserAgentParserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;

class AdminSessionController extends Controller
{
    /**
     * Hiển thị trang quản lý phiên đăng nhập
     */
    public function index()
    {
        $stats = SessionTrackingService::getStats();

        // Danh sách tài khoản phục vụ bộ lọc
        $users = User::query()
            ->select('id', 'name', 'email', 'citizen_id', 'employee_code', 'branch_id')
            ->with(['branch' => fn($q) => $q->select('id', 'branch_name')])
            ->orderBy('name')
            ->get();

        return view('admin.sessions.index', compact('stats', 'users'));
    }

    /**
     * Dữ liệu phân trang DataTables cho danh sách phiên
     */
    public function data(Request $request)
    {
        $now = Carbon::now();

        // Subquery đếm số phiên active của từng user
        $activeSessionsCountSubquery = DB::table('login_sessions')
            ->select('user_id', DB::raw('COUNT(*) as active_count'))
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->groupBy('user_id');

        $query = LoginSession::query()
            ->leftJoin('users', 'login_sessions.user_id', '=', 'users.id')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->leftJoinSub($activeSessionsCountSubquery, 'user_active_sessions', function ($join) {
                $join->on('login_sessions.user_id', '=', 'user_active_sessions.user_id');
            })
            ->select([
                'login_sessions.id',
                'login_sessions.session_id',
                'login_sessions.user_id',
                'login_sessions.ip_address',
                'login_sessions.user_agent',
                'login_sessions.device_type',
                'login_sessions.browser',
                'login_sessions.platform',
                'login_sessions.login_at',
                'login_sessions.last_activity_at',
                'login_sessions.expires_at',
                'login_sessions.status',
                'login_sessions.ended_at',
                'login_sessions.ended_reason',
                'login_sessions.ended_by_user_id',
                'users.name as user_name',
                'users.email as user_email',
                'users.role_id as user_role_id',
                'users.employee_code as user_employee_code',
                'branches.branch_name',
                'departments.department_name',
                DB::raw('COALESCE(user_active_sessions.active_count, 0) as user_active_count'),
            ]);

        // Lọc theo người dùng
        if ($request->filled('user_id')) {
            $query->where('login_sessions.user_id', $request->input('user_id'));
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('login_sessions.status', 'active')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('login_sessions.expires_at')
                            ->orWhere('login_sessions.expires_at', '>', $now);
                    });
            } elseif ($status === 'expired') {
                $query->where(function ($q) use ($now) {
                    $q->where('login_sessions.status', 'expired')
                        ->orWhere(function ($sub) use ($now) {
                            $sub->where('login_sessions.status', 'active')
                                ->whereNotNull('login_sessions.expires_at')
                                ->where('login_sessions.expires_at', '<=', $now);
                        });
                });
            } elseif ($status === 'revoked') {
                $query->where('login_sessions.status', 'revoked');
            } elseif ($status === 'ended') {
                $query->where('login_sessions.status', 'ended');
            } elseif ($status === 'ended_or_revoked') {
                $query->where(function ($q) use ($now) {
                    $q->whereIn('login_sessions.status', ['revoked', 'ended', 'expired'])
                        ->orWhere(function ($sub) use ($now) {
                            $sub->where('login_sessions.status', 'active')
                                ->whereNotNull('login_sessions.expires_at')
                                ->where('login_sessions.expires_at', '<=', $now);
                        });
                });
            }
        }

        // Lọc theo loại thiết bị
        if ($request->filled('device_type')) {
            $query->where('login_sessions.device_type', $request->input('device_type'));
        }

        // Lọc theo khoảng ngày đăng nhập
        if ($request->filled('date_from')) {
            $query->where('login_sessions.login_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('login_sessions.login_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        // Tìm kiếm theo từ khóa
        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('users.name', 'like', "%{$keyword}%")
                        ->orWhere('users.email', 'like', "%{$keyword}%")
                        ->orWhere('users.employee_code', 'like', "%{$keyword}%")
                        ->orWhere('login_sessions.ip_address', 'like', "%{$keyword}%")
                        ->orWhere('login_sessions.browser', 'like', "%{$keyword}%")
                        ->orWhere('login_sessions.platform', 'like', "%{$keyword}%")
                        ->orWhere('login_sessions.session_id', 'like', "%{$keyword}%");
                });
            }
        }

        $query->orderByDesc('login_sessions.last_activity_at');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('user_info', function ($row) {
                if (!$row->user_id) {
                    return '<span class="text-muted font-italic">Tài khoản chưa định danh / Khách</span>';
                }

                $activeCountBadge = (int) $row->user_active_count > 0
                    ? '<span class="badge badge-pill badge-primary ml-1 btn-filter-user" style="cursor: pointer;" data-user-id="' . $row->user_id . '" title="Bấm để lọc tất cả phiên của ' . e($row->user_name) . '">' . $row->user_active_count . ' phiên đang mở</span>'
                    : '<span class="badge badge-pill badge-light text-muted ml-1">0 phiên</span>';

                $dept = e($row->department_name ?: ($row->branch_name ?: 'Chi nhánh'));
                $empCode = $row->user_employee_code ? '<span class="badge badge-secondary mr-1">' . e($row->user_employee_code) . '</span>' : '';

                return '
                    <div class="d-flex align-items-center">
                        <div class="user-avatar-circle mr-2 bg-gradient-primary text-white font-weight-bold d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; border-radius: 50%;">
                            ' . mb_substr($row->user_name ?: 'U', 0, 1, 'UTF-8') . '
                        </div>
                        <div>
                            <div class="font-weight-bold"><a href="javascript:void(0);" class="text-gray-900 btn-filter-user text-decoration-none" data-user-id="' . $row->user_id . '" title="Bấm để lọc tất cả phiên của ' . e($row->user_name) . '">' . e($row->user_name) . '</a> ' . $activeCountBadge . '</div>
                            <div class="small text-muted">' . $empCode . e($row->user_email) . '</div>
                            <div class="small text-xs text-primary">' . $dept . '</div>
                        </div>
                    </div>
                ';
            })
            ->addColumn('device_info', function ($row) {
                $parsed = UserAgentParserService::parse($row->user_agent);
                $deviceBadge = match ($parsed['device_type']) {
                    'mobile' => '<span class="badge badge-light border text-info"><i class="fas fa-mobile-alt mr-1"></i>Mobile</span>',
                    'tablet' => '<span class="badge badge-light border text-purple"><i class="fas fa-tablet-alt mr-1"></i>Tablet</span>',
                    'bot' => '<span class="badge badge-light border text-dark"><i class="fas fa-robot mr-1"></i>Bot/API</span>',
                    default => '<span class="badge badge-light border text-secondary"><i class="fas fa-desktop mr-1"></i>Máy tính</span>',
                };

                return '
                    <div>
                        <div class="font-weight-bold small text-gray-800">
                            <i class="' . $parsed['browser_icon'] . ' mr-1 text-primary"></i>' . e($parsed['browser']) . '
                        </div>
                        <div class="small text-muted">
                            <i class="' . $parsed['platform_icon'] . ' mr-1 text-secondary"></i>' . e($parsed['platform']) . '
                        </div>
                        <div class="mt-1">' . $deviceBadge . '</div>
                    </div>
                ';
            })
            ->addColumn('ip_info', function ($row) {
                $ip = e($row->ip_address ?: '127.0.0.1');
                return '
                    <div>
                        <code class="text-dark font-weight-bold">' . $ip . '</code>
                        <button type="button" class="btn btn-sm btn-link p-0 ml-1 text-muted btn-copy-ip" data-ip="' . $ip . '" title="Sao chép IP">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                ';
            })
            ->addColumn('timeline', function ($row) {
                $loginTime = $row->login_at ? Carbon::parse($row->login_at) : null;
                $lastActive = $row->last_activity_at ? Carbon::parse($row->last_activity_at) : null;

                $loginFormatted = $loginTime ? $loginTime->format('d/m/Y H:i:s') : '—';
                $lastActiveFormatted = $lastActive ? $lastActive->format('d/m/Y H:i:s') : '—';
                $diffForHumans = $lastActive ? $lastActive->diffForHumans() : '—';

                return '
                    <div class="small">
                        <div><span class="text-muted">Đăng nhập:</span> <span class="font-weight-bold">' . $loginFormatted . '</span></div>
                        <div><span class="text-muted">Hoạt động cuối:</span> <span class="font-weight-bold text-primary" title="' . $lastActiveFormatted . '">' . $diffForHumans . '</span></div>
                    </div>
                ';
            })
            ->addColumn('status_badge', function ($row) use ($now) {
                $isExpired = false;
                if ($row->status === 'active' && $row->expires_at && Carbon::parse($row->expires_at)->isPast()) {
                    $isExpired = true;
                }

                if ($row->status === 'revoked') {
                    $reason = e($row->ended_reason === 'admin_revoked_all' ? 'Admin thu hồi hàng loạt' : 'Admin thu hồi');
                    return '<span class="badge badge-danger p-2"><i class="fas fa-ban mr-1"></i>Đã thu hồi (' . $reason . ')</span>';
                }

                if ($row->status === 'ended') {
                    return '<span class="badge badge-secondary p-2"><i class="fas fa-sign-out-alt mr-1"></i>Đã đăng xuất</span>';
                }

                if ($row->status === 'expired' || $isExpired) {
                    return '<span class="badge badge-warning p-2"><i class="fas fa-clock mr-1"></i>Đã hết hạn</span>';
                }

                return '<span class="badge badge-success p-2"><i class="fas fa-circle text-xs mr-1 animate-pulse"></i>Đang hoạt động</span>';
            })
            ->addColumn('actions', function ($row) use ($now) {
                $isAlive = ($row->status === 'active') && (!$row->expires_at || Carbon::parse($row->expires_at)->isFuture());

                $detailBtn = '
                    <button type="button" class="btn btn-sm btn-info btn-session-detail mb-1" data-id="' . $row->id . '" title="Xem chi tiết phiên">
                        <i class="fas fa-info-circle"></i> Chi tiết
                    </button>
                ';

                $revokeBtn = '';
                if ($isAlive) {
                    $revokeBtn = '
                        <button type="button" class="btn btn-sm btn-danger btn-session-revoke mb-1" 
                            data-id="' . $row->id . '" 
                            data-user="' . e($row->user_name) . '" 
                            data-ip="' . e($row->ip_address) . '"
                            title="Kết thúc phiên này ngay lập tức">
                            <i class="fas fa-power-off"></i> Thu hồi
                        </button>
                    ';
                }

                $revokeAllBtn = '';
                if ($row->user_id && (int) $row->user_active_count > 0) {
                    $revokeAllBtn = '
                        <button type="button" class="btn btn-sm btn-outline-danger btn-session-revoke-all mb-1" 
                            data-user-id="' . $row->user_id . '" 
                            data-user="' . e($row->user_name) . '"
                            data-count="' . $row->user_active_count . '"
                            title="Đăng xuất tài khoản này khỏi tất cả thiết bị">
                            <i class="fas fa-users-slash"></i> Đăng xuất tất cả
                        </button>
                    ';
                }

                $deleteBtn = '';
                if (!$isAlive) {
                    $deleteBtn = '
                        <button type="button" class="btn btn-sm btn-outline-danger btn-session-delete mb-1" 
                            data-id="' . $row->id . '" 
                            data-user="' . e($row->user_name) . '"
                            title="Xóa bản ghi phiên này khỏi hệ thống">
                            <i class="fas fa-trash-alt"></i> Xóa
                        </button>
                    ';
                }

                return '
                    <div class="d-flex flex-column align-items-stretch" style="min-width: 110px;">
                        ' . $detailBtn . '
                        ' . $revokeBtn . '
                        ' . $revokeAllBtn . '
                        ' . $deleteBtn . '
                    </div>
                ';
            })
            ->rawColumns(['user_info', 'device_info', 'ip_info', 'timeline', 'status_badge', 'actions'])
            ->with('stats', SessionTrackingService::getStats())
            ->make(true);
    }

    /**
     * Lấy chi tiết thông tin phiên (cho Modal)
     */
    public function show(int $id)
    {
        $session = LoginSession::with(['user.branch', 'user.department', 'user.position', 'endedBy'])->findOrFail($id);
        $uaParsed = UserAgentParserService::parse($session->user_agent);

        $now = Carbon::now();
        $effectiveStatus = $session->getEffectiveStatus();

        return response()->json([
            'status' => true,
            'session' => [
                'id' => $session->id,
                'session_id' => $session->session_id,
                'user_id' => $session->user_id,
                'user_name' => $session->user?->name ?? 'Chưa xác định',
                'user_email' => $session->user?->email ?? '—',
                'user_citizen_id' => $session->user?->citizen_id ?? '—',
                'user_employee_code' => $session->user?->employee_code ?? '—',
                'branch_name' => $session->user?->branch?->branch_name ?? '—',
                'department_name' => $session->user?->department?->department_name ?? '—',
                'position_name' => $session->user?->position?->position_name ?? '—',
                'ip_address' => $session->ip_address ?: '127.0.0.1',
                'user_agent' => $session->user_agent,
                'ua_parsed' => $uaParsed,
                'login_at' => $session->login_at ? $session->login_at->format('d/m/Y H:i:s') : '—',
                'last_activity_at' => $session->last_activity_at ? $session->last_activity_at->format('d/m/Y H:i:s') : '—',
                'last_activity_human' => $session->last_activity_at ? $session->last_activity_at->diffForHumans() : '—',
                'expires_at' => $session->expires_at ? $session->expires_at->format('d/m/Y H:i:s') : '—',
                'status' => $effectiveStatus,
                'raw_status' => $session->status,
                'ended_at' => $session->ended_at ? $session->ended_at->format('d/m/Y H:i:s') : '—',
                'ended_reason' => $session->ended_reason,
                'ended_by_name' => $session->endedBy?->name ?? '—',
            ],
        ]);
    }

    /**
     * Thu hồi / kết thúc 1 phiên đã chọn
     */
    public function revoke(Request $request, int $id)
    {
        $adminId = (int) (Session::get('admin_id') ?: 1);
        $reason = $request->input('reason', 'Thu hồi bởi Quản trị viên');

        $session = LoginSession::find($id);
        if (!$session) {
            return response()->json([
                'status' => false,
                'message' => 'Phiên đăng nhập không tồn tại hoặc đã bị xóa.',
            ], 404);
        }

        $success = SessionTrackingService::revokeSession($id, $adminId, $reason);

        if ($success) {
            return response()->json([
                'status' => true,
                'message' => 'Đã kết thúc phiên làm việc thành công! Phiên này sẽ bị vô hiệu hóa ngay tức thì ở request tiếp theo.',
                'stats' => SessionTrackingService::getStats(),
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Có lỗi xảy ra khi kết thúc phiên.',
        ], 500);
    }

    /**
     * Thu hồi tất cả phiên của một tài khoản
     */
    public function revokeAll(Request $request, int $userId)
    {
        $adminId = (int) (Session::get('admin_id') ?: 1);
        $reason = $request->input('reason', 'Thu hồi tất cả phiên bởi Quản trị viên');

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại.',
            ], 404);
        }

        $revokedCount = SessionTrackingService::revokeAllUserSessions($userId, $adminId, $reason);

        return response()->json([
            'status' => true,
            'message' => "Đã kết thúc thành công {$revokedCount} phiên đăng nhập của người dùng {$user->name}!",
            'revoked_count' => $revokedCount,
            'stats' => SessionTrackingService::getStats(),
        ]);
    }

    /**
     * Xóa 1 bản ghi phiên đăng nhập
     */
    public function destroy(Request $request, int $id)
    {
        $adminId = (int) (Session::get('admin_id') ?: 1);

        $session = LoginSession::find($id);
        if (!$session) {
            return response()->json([
                'status' => false,
                'message' => 'Phiên đăng nhập không tồn tại hoặc đã bị xóa.',
            ], 404);
        }

        $success = SessionTrackingService::deleteSession($id, $adminId);

        if ($success) {
            return response()->json([
                'status' => true,
                'message' => 'Đã xóa bản ghi phiên đăng nhập thành công khỏi hệ thống!',
                'stats' => SessionTrackingService::getStats(),
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Có lỗi xảy ra khi xóa phiên đăng nhập.',
        ], 500);
    }

    /**
     * Dọn dẹp hàng loạt các phiên đăng nhập cũ / không còn hoạt động
     */
    public function clean(Request $request)
    {
        $adminId = (int) (Session::get('admin_id') ?: 1);
        $days = (int) $request->input('days', 0);

        $deletedCount = SessionTrackingService::cleanInactiveSessions($adminId, $days);

        return response()->json([
            'status' => true,
            'message' => "Đã dọn dẹp thành công {$deletedCount} phiên đăng nhập cũ khỏi hệ thống!",
            'deleted_count' => $deletedCount,
            'stats' => SessionTrackingService::getStats(),
        ]);
    }
}
