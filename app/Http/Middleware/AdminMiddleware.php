<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->hasSession() ? $request->session() : session();
        $adminId = $session ? $session->get('admin_id') : null;
        if (empty($adminId)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.'], 401);
            }
            return redirect()->route('login_admin');
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
                    'message' => 'Phiên làm việc của Quản trị viên đã bị kết thúc.'
                ], 401);
            }
            return redirect()->route('login_admin')->with('error', 'Phiên làm việc của bạn đã bị kết thúc.');
        }

        if ($sessionId) {
            \App\Services\SessionTrackingService::touch($sessionId, (int) $adminId);
        }

        return $next($request);
    }
}
