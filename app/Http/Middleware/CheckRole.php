<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $userId = $request->hasSession() ? $request->session()->get('user_id') : null;

        // 1. Admin login session in Admin routes or pure admin session
        if ($request->hasSession() && $request->session()->has('admin_id') && ($request->is('admin*') || ! $userId)) {
            $adminId = (int) $request->session()->get('admin_id');
            $admin = User::find($adminId);
            if ($admin && ((int) $admin->role_id === 0 || $admin->hasRole('admin') || $admin->hasRole($roles))) {
                return $next($request);
            }
        }

        // 2. User login session
        if (! $userId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Phiên đăng nhập đã hết. Vui lòng đăng nhập lại.'], 401);
            }
            return redirect()->route('login');
        }

        $session = $request->hasSession() ? $request->session() : null;
        $syncResult = User::syncSessionPermissionsIfNeeded($userId, $session);
        if ($syncResult === false) {
            if ($session) {
                $session->flush();
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tài khoản của bạn đã bị khóa hoặc không còn hoạt động.'], 403);
            }
            return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa hoặc không còn hoạt động.');
        }

        $sessionRoles = $session ? $session->get('user_roles', []) : [];
        if (is_array($sessionRoles)) {
            if (in_array('admin', $sessionRoles, true)) {
                return $next($request);
            }
            foreach ($roles as $role) {
                if (in_array($role, $sessionRoles, true)) {
                    return $next($request);
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn không có vai trò phù hợp để truy cập chức năng này.'
            ], 403);
        }

        return redirect()->route('index')->with('error', 'Bạn không có vai trò phù hợp để truy cập chức năng này.');
    }
}
