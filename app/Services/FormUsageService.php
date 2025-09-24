<?php

namespace App\Services;

use App\Models\SupportFormUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class FormUsageService
{
    public static function log(int $formId): void
    {
        $userId = Session::get('user_id');
        if (!$userId) return;

        $now = now();

        SupportFormUsage::upsert(
            [[
                'user_id' => $userId,
                'support_form_id' => $formId,
                'used_at' => $now,
                'updated_at' => $now,
                'created_at' => $now
            ]],
            ['user_id', 'support_form_id'],
            ['used_at', 'updated_at']
        );
        // Chỉ giữ tối đa 20 record
        $count = SupportFormUsage::where('user_id', $userId)->count();
        if ($count > 20) {
            SupportFormUsage::where('user_id', $userId)
                ->orderBy('used_at', 'asc')
                ->take($count - 20)
                ->delete();
        }
    }
}
