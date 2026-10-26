<?php

namespace App\Models;

use App\Services\UserAgentParserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginSession extends Model
{
    protected $table = 'login_sessions';

    protected $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'login_at',
        'last_activity_at',
        'expires_at',
        'status',
        'ended_at',
        'ended_reason',
        'ended_by_user_id',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by_user_id');
    }

    /**
     * Xác định trạng thái thực tế của phiên:
     * - 'revoked': Quản trị viên thu hồi
     * - 'ended': Người dùng đăng xuất chủ động
     * - 'expired': Hết hạn theo session lifetime
     * - 'active': Đang hoạt động bình thường
     */
    public function getEffectiveStatus(): string
    {
        if ($this->status === 'revoked') {
            return 'revoked';
        }
        if ($this->status === 'ended') {
            return 'ended';
        }
        if ($this->status === 'expired') {
            return 'expired';
        }

        // Kiểm tra thời hạn hết hạn nếu đang có cờ active
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }

    public function isAlive(): bool
    {
        return $this->getEffectiveStatus() === 'active';
    }

    /**
     * Trả về thông tin phân tích User Agent dạng đối tượng mảng
     */
    public function getUserAgentParsedAttribute(): array
    {
        return UserAgentParserService::parse($this->user_agent);
    }

    /**
     * Scope lọc các phiên đang còn hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    /**
     * Scope lọc các phiên đã bị thu hồi hoặc đã kết thúc
     */
    public function scopeEnded($query)
    {
        return $query->whereIn('status', ['revoked', 'ended']);
    }

    /**
     * Scope lọc các phiên đã hết hạn
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere(function ($sub) {
                    $sub->where('status', 'active')
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', Carbon::now());
                });
        });
    }
}
