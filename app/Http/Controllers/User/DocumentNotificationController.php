<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DocumentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

class DocumentNotificationController extends Controller
{
    public function __invoke(DocumentNotificationService $notifications): JsonResponse
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return response()->json(['message' => 'Phiên đăng nhập đã hết.'], 401);
        }

        $user = User::with([
            'branch:id,branch_type',
            'position:id,level,position_name',
        ])->find($userId);
        if (! $user) {
            return response()->json(['message' => 'Không tìm thấy người dùng.'], 401);
        }

        return response()->json($notifications->unread($user))
            ->header('Cache-Control', 'private, no-store');
    }
}
