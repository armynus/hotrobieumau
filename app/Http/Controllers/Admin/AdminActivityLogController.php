<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\UserAgentParserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdminActivityLogController extends Controller
{
    /**
     * Hiển thị giao diện danh sách nhật ký hoạt động
     */
    public function index()
    {
        $stats = [
            'total' => ActivityLog::count(),
            'login_success' => ActivityLog::where('action', 'auth.login.success')->count(),
            'business_ops' => ActivityLog::where('module', '!=', 'auth')->count(),
            'failed_or_warn' => ActivityLog::whereIn('status', ['failed', 'warning'])->count(),
        ];

        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $modules = ActivityLog::select('module', 'module_name')
            ->distinct()
            ->orderBy('module_name')
            ->get();

        $actions = ActivityLog::select('action', 'action_label')
            ->distinct()
            ->orderBy('action_label')
            ->get();

        return view('admin.audit_logs.index', compact('stats', 'users', 'modules', 'actions'));
    }

    /**
     * Cung cấp dữ liệu DataTables qua AJAX
     */
    public function data(Request $request)
    {
        $query = ActivityLog::query()
            ->leftJoin('users', 'activity_logs.user_id', '=', 'users.id')
            ->select([
                'activity_logs.id',
                'activity_logs.user_id',
                'activity_logs.user_name',
                'activity_logs.user_email',
                'activity_logs.action',
                'activity_logs.action_label',
                'activity_logs.module',
                'activity_logs.module_name',
                'activity_logs.target_type',
                'activity_logs.target_id',
                'activity_logs.target_label',
                'activity_logs.description',
                'activity_logs.ip_address',
                'activity_logs.user_agent',
                'activity_logs.browser',
                'activity_logs.platform',
                'activity_logs.device_type',
                'activity_logs.status',
                'activity_logs.details',
                'activity_logs.created_at',
            ]);

        // Lọc người thực hiện
        if ($request->filled('user_id')) {
            $query->where('activity_logs.user_id', $request->input('user_id'));
        }

        // Lọc theo phân hệ (module)
        if ($request->filled('module')) {
            $query->where('activity_logs.module', $request->input('module'));
        }

        // Lọc theo loại hành động (action)
        if ($request->filled('action')) {
            $query->where('activity_logs.action', $request->input('action'));
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('activity_logs.status', $request->input('status'));
        }

        // Lọc khoảng ngày
        if ($request->filled('date_from')) {
            $query->where('activity_logs.created_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('activity_logs.created_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        // Tìm kiếm từ khóa
        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('activity_logs.description', 'like', "%{$keyword}%")
                        ->orWhere('activity_logs.user_name', 'like', "%{$keyword}%")
                        ->orWhere('activity_logs.user_email', 'like', "%{$keyword}%")
                        ->orWhere('activity_logs.target_label', 'like', "%{$keyword}%")
                        ->orWhere('activity_logs.ip_address', 'like', "%{$keyword}%")
                        ->orWhere('activity_logs.action_label', 'like', "%{$keyword}%");
                });
            }
        }

        $query->orderByDesc('activity_logs.created_at');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('performer', function ($row) {
                if (!$row->user_id && !$row->user_name) {
                    return '<div class="text-muted font-italic"><i class="fas fa-user-secret mr-1"></i>Khách vãng lai / Chưa đăng nhập</div>';
                }

                $name = e($row->user_name ?: 'Hệ thống');
                $email = e($row->user_email ?: '—');
                $letter = mb_substr($name, 0, 1, 'UTF-8');

                return '
                    <div class="d-flex align-items-center">
                        <div class="user-avatar-circle mr-2 bg-gradient-info text-white font-weight-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 50%; font-size: 0.85rem;">
                            ' . $letter . '
                        </div>
                        <div>
                            <div class="font-weight-bold text-gray-900 small">' . $name . '</div>
                            <div class="text-xs text-muted">' . $email . '</div>
                        </div>
                    </div>
                ';
            })
            ->addColumn('action_column', function ($row) {
                $moduleBadge = match ($row->module) {
                    'auth' => '<span class="badge badge-primary px-2 py-1"><i class="fas fa-key mr-1"></i>Xác thực</span>',
                    'sessions' => '<span class="badge badge-danger px-2 py-1"><i class="fas fa-desktop mr-1"></i>Phiên làm việc</span>',
                    'users' => '<span class="badge badge-info px-2 py-1"><i class="fas fa-user-cog mr-1"></i>Người dùng</span>',
                    'roles' => '<span class="badge badge-dark px-2 py-1"><i class="fas fa-shield-alt mr-1"></i>Phân quyền</span>',
                    'branches' => '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-building mr-1"></i>Chi nhánh</span>',
                    'forms' => '<span class="badge badge-success px-2 py-1"><i class="fas fa-file-alt mr-1"></i>Biểu mẫu</span>',
                    'it_support' => '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-headset mr-1"></i>Hỗ trợ IT</span>',
                    default => '<span class="badge badge-light border px-2 py-1">' . e($row->module_name ?: $row->module) . '</span>',
                };

                return '
                    <div>
                        <div class="font-weight-bold text-gray-800 small">' . e($row->action_label) . '</div>
                        <div class="mt-1">' . $moduleBadge . '</div>
                    </div>
                ';
            })
            ->addColumn('target_info', function ($row) {
                if (!$row->target_label && !$row->target_type) {
                    return '<span class="text-muted small">—</span>';
                }

                $type = $row->target_type ? '<span class="badge badge-light border text-muted mr-1">' . e($row->target_type) . '</span>' : '';
                return '
                    <div class="small">
                        ' . $type . '
                        <span class="font-weight-bold text-gray-800">' . e($row->target_label) . '</span>
                    </div>
                ';
            })
            ->addColumn('description_column', function ($row) {
                $statusBadge = match ($row->status) {
                    'failed' => '<span class="badge badge-danger ml-1"><i class="fas fa-times-circle mr-1"></i>Thất bại</span>',
                    'warning' => '<span class="badge badge-warning text-dark ml-1"><i class="fas fa-exclamation-triangle mr-1"></i>Cảnh báo</span>',
                    default => '<span class="badge badge-success ml-1"><i class="fas fa-check-circle mr-1"></i>Thành công</span>',
                };

                return '
                    <div class="small">
                        <div class="text-gray-900">' . e($row->description) . '</div>
                        <div class="mt-1">' . $statusBadge . '</div>
                    </div>
                ';
            })
            ->addColumn('network_info', function ($row) {
                $ip = e($row->ip_address ?: '127.0.0.1');
                $parsed = UserAgentParserService::parse($row->user_agent);

                return '
                    <div class="small">
                        <div>
                            <code class="text-dark font-weight-bold">' . $ip . '</code>
                            <button type="button" class="btn btn-sm btn-link p-0 ml-1 text-muted btn-copy-ip" data-ip="' . $ip . '" title="Sao chép IP">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                        <div class="text-muted text-xs mt-1">
                            <i class="' . $parsed['browser_icon'] . ' mr-1 text-primary"></i>' . e($parsed['browser']) . ' 
                            <span class="text-gray-400">|</span> 
                            <i class="' . $parsed['platform_icon'] . ' mr-1"></i>' . e($parsed['platform']) . '
                        </div>
                    </div>
                ';
            })
            ->addColumn('created_time', function ($row) {
                $time = Carbon::parse($row->created_at);
                return '
                    <div class="small">
                        <div class="font-weight-bold text-gray-800">' . $time->format('d/m/Y H:i:s') . '</div>
                        <div class="text-xs text-muted">' . $time->diffForHumans() . '</div>
                    </div>
                ';
            })
            ->addColumn('actions', function ($row) {
                return '
                    <div class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-info btn-log-detail" data-id="' . $row->id . '" title="Xem chi tiết nhật ký">
                            <i class="fas fa-eye"></i> Chi tiết
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['performer', 'action_column', 'target_info', 'description_column', 'network_info', 'created_time', 'actions'])
            ->make(true);
    }

    /**
     * Chi tiết bản ghi nhật ký (cho Modal)
     */
    public function show(int $id)
    {
        $log = ActivityLog::with('user')->findOrFail($id);
        $uaParsed = UserAgentParserService::parse($log->user_agent);

        return response()->json([
            'status' => true,
            'log' => [
                'id' => $log->id,
                'user_name' => $log->user_name ?: ($log->user?->name ?? 'Khách vãng lai / Chưa đăng nhập'),
                'user_email' => $log->user_email ?: ($log->user?->email ?? '—'),
                'action' => $log->action,
                'action_label' => $log->action_label,
                'module' => $log->module,
                'module_name' => $log->module_name,
                'target_type' => $log->target_type,
                'target_id' => $log->target_id,
                'target_label' => $log->target_label,
                'description' => $log->description,
                'ip_address' => $log->ip_address ?: '127.0.0.1',
                'user_agent' => $log->user_agent,
                'ua_parsed' => $uaParsed,
                'status' => $log->status,
                'details' => $log->details,
                'created_at' => $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—',
                'created_at_human' => $log->created_at ? $log->created_at->diffForHumans() : '—',
            ],
        ]);
    }
}
