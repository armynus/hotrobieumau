<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $userId = $request->hasSession() ? $request->session()->get('user_id') : null;

        // 1. Admin login session in Admin routes or pure admin session
        if ($request->hasSession() && $request->session()->has('admin_id') && ($request->is('admin*') || ! $userId)) {
            $adminId = (int) $request->session()->get('admin_id');
            $admin = User::find($adminId);
            if ($admin && ((int) $admin->role_id === 0 || $admin->hasRole('admin') || $request->is('admin*'))) {
                return $next($request);
            }
        }

        // 2. User login session
        if (! $userId) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Phiên đăng nhập đã hết. Vui lòng đăng nhập lại.'], 401);
            }
            return redirect()->route('login');
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
            return $next($request);
        }

        // Auto-sync permissions/roles from DB if version changed or cache missing, and verify user is active
        $session = $request->hasSession() ? $request->session() : null;
        $syncResult = User::syncSessionPermissionsIfNeeded($userId, $session);
        if ($syncResult === false) {
            if ($session) {
                $session->flush();
            }
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Tài khoản của bạn đã bị khóa hoặc không còn hoạt động.'], 403);
            }
            return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa hoặc không còn hoạt động.');
        }

        // Flatten permissions supporting comma or pipe separation: e.g. "perm1,perm2" or "perm1|perm2"
        $requiredPermissions = [];
        foreach ($permissions as $p) {
            foreach (preg_split('/[,|]/', (string) $p) as $sub) {
                $sub = trim($sub);
                if ($sub !== '') {
                    $requiredPermissions[] = $sub;
                }
            }
        }

        // Filter out any permission that is globally inactive on the system
        $activeRequired = array_filter($requiredPermissions, function ($p) {
            return \App\Models\Permission::isFeatureActive($p);
        });

        if (empty($activeRequired)) {
            // All required permissions for this route are currently disabled/inactive system-wide!
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tính năng này hiện đang tạm thời tắt hoặc bạn không có quyền truy cập.'
                ], 403);
            }
            return redirect()->route('index')->with('error', 'Chức năng này hiện đang tạm dừng hoặc bạn không có quyền truy cập.');
        }

        $userPerms = is_array($syncResult) ? $syncResult : ($session ? $session->get('user_permissions', []) : []);

        foreach ($activeRequired as $perm) {
            if (in_array('*', $userPerms, true) || in_array($perm, $userPerms, true)) {
                return $next($request);
            }
        }

        $permLabel = implode(', ', $requiredPermissions);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn không có quyền thực hiện thao tác này (yêu cầu quyền: ' . $permLabel . ').'
            ], 403);
        }

        return redirect()->route('index')->with('error', 'Bạn không có quyền truy cập chức năng này.');
    }
}
