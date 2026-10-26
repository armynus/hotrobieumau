<?php

namespace App\Models;

use App\Services\UserAgentParserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'action',
        'action_label',
        'module',
        'module_name',
        'target_type',
        'target_id',
        'target_label',
        'description',
        'ip_address',
        'user_agent',
        'browser',
        'platform',
        'device_type',
        'status',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUserAgentParsedAttribute(): array
    {
        return UserAgentParserService::parse($this->user_agent);
    }

    public function scopeModule($query, ?string $module)
    {
        if ($module) {
            return $query->where('module', $module);
        }
        return $query;
    }

    public function scopeAction($query, ?string $action)
    {
        if ($action) {
            return $query->where('action', $action);
        }
        return $query;
    }

    public function scopeStatus($query, ?string $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeDateBetween($query, ?string $startDate, ?string $endDate)
    {
        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }
        return $query;
    }
}
