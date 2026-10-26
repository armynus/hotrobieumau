<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TenantDatabase
{
    public function handle($request, Closure $next)
    {

        $session = $request->hasSession() ? $request->session() : session();
        $userId = $session ? $session->get('user_id') : null;

        // Kiểm tra xem user đã đăng nhập chưa
        if (empty($userId)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Phiên đăng nhập đã hết. Vui lòng đăng nhập lại.'], 401);
            }
            return redirect()->route('login');
        }

        $sessionId = $session ? $session->getId() : null;
        if ($sessionId && \App\Services\SessionTrackingService::isSessionRevoked($sessionId)) {
            if ($session) {
                $session->flush();
                $session->invalidate();
            }
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Phiên đăng nhập của bạn đã bị kết thúc bởi Quản trị viên.'
                ], 401);
            }
            return redirect()->route('login')->with('error', 'Phiên đăng nhập của bạn đã bị kết thúc bởi Quản trị viên.');
        }

        if ($sessionId) {
            \App\Services\SessionTrackingService::touch($sessionId, (int) $userId);
        }
        $syncResult = \App\Models\User::syncSessionPermissionsIfNeeded($userId, $session);
        if ($syncResult === false) {
            if ($session) {
                $session->flush();
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tài khoản của bạn đã bị khóa hoặc không còn hoạt động.'], 403);
            }
            return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa hoặc không còn hoạt động.');
        }

        // Lấy thông tin chi nhánh của user từ session
        $branchId = $session ? $session->get('UserBranchId') : session('UserBranchId');

        if ($branchId) {
            $databaseName = 'branch_' . $branchId;
            $currentConfiguredDb = Config::get('database.connections.tenant.database');

            if ($currentConfiguredDb !== $databaseName) {
                Config::set('database.connections.tenant.database', $databaseName);
                DB::purge('tenant');
            }
        }

        return $next($request);
    }
}
