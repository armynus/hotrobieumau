<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'module',
        'module_name',
        'description',
        'is_system',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'permission_id', 'role_id');
    }

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    protected static array $memoizedStatusColumnExists = [];
    protected static ?array $memoizedActiveFeatureMap = null;

    /**
     * Clear memoized permission state (useful for tests or after updates)
     */
    public static function clearActiveFeaturesCache(): void
    {
        static::$memoizedActiveFeatureMap = null;
        static::$memoizedStatusColumnExists = [];
        \Illuminate\Support\Facades\Cache::forget('active_feature_slugs_map');
    }

    /**
     * Get array map of permission slugs to active status: [slug => bool]
     */
    public static function getActiveFeatureSlugsMap(): array
    {
        if (static::$memoizedActiveFeatureMap !== null) {
            return static::$memoizedActiveFeatureMap;
        }

        if (!\Illuminate\Support\Facades\Facade::getFacadeApplication()) {
            return [];
        }

        $conn = config('database.default', 'mysql');
        if (!isset(static::$memoizedStatusColumnExists[$conn])) {
            static::$memoizedStatusColumnExists[$conn] = \Illuminate\Support\Facades\Schema::connection($conn)->hasTable('permissions')
                && \Illuminate\Support\Facades\Schema::connection($conn)->hasColumn('permissions', 'status');
        }

        if (!static::$memoizedStatusColumnExists[$conn]) {
            return [];
        }

        static::$memoizedActiveFeatureMap = \Illuminate\Support\Facades\Cache::remember('active_feature_slugs_map', 3600, function () {
            return static::pluck('status', 'slug')
                ->map(fn ($status) => ($status ?? 'active') === 'active')
                ->toArray();
        });

        return static::$memoizedActiveFeatureMap;
    }

    /**
     * Check if a feature/permission is currently active on the system.
     * Returns false if the permission is explicitly inactive/disabled.
     */
    public static function isFeatureActive(string $slug): bool
    {
        if (static::$memoizedActiveFeatureMap !== null) {
            return static::$memoizedActiveFeatureMap[$slug] ?? true;
        }

        $activeMap = static::getActiveFeatureSlugsMap();
        return $activeMap[$slug] ?? true;
    }
}
