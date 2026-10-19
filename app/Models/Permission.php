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

    /**
     * Check if a feature/permission is currently active on the system.
     * Returns false if the permission is explicitly inactive/disabled.
     */
    public static function isFeatureActive(string $slug): bool
    {
        if (!\Illuminate\Support\Facades\Facade::getFacadeApplication()) {
            return true;
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('permissions') || !\Illuminate\Support\Facades\Schema::hasColumn('permissions', 'status')) {
            return true;
        }

        return \Illuminate\Support\Facades\Cache::remember('perm_active_' . $slug, 300, function () use ($slug) {
            $perm = static::where('slug', $slug)->first();
            return $perm ? (($perm->status ?? 'active') === 'active') : true;
        });
    }
}
