<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'status',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission', 'role_id', 'permission_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role', 'role_id', 'user_id');
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions->contains('slug', $permissionSlug);
    }

    protected static ?int $memoizedPermissionsVersion = null;

    public static function permissionsVersion(): int
    {
        if (static::$memoizedPermissionsVersion !== null) {
            return static::$memoizedPermissionsVersion;
        }

        static::$memoizedPermissionsVersion = (int) \Illuminate\Support\Facades\Cache::get('rbac_permissions_version', 1);
        return static::$memoizedPermissionsVersion;
    }

    public static function bumpPermissionsVersion(): int
    {
        static::$memoizedPermissionsVersion = null;
        if (class_exists(User::class)) {
            User::clearUserPermissionsVersionCache();
        }
        $newVersion = (int) \Illuminate\Support\Facades\Cache::get('rbac_permissions_version', 1) + 1;
        \Illuminate\Support\Facades\Cache::forever('rbac_permissions_version', $newVersion);
        static::$memoizedPermissionsVersion = $newVersion;
        return $newVersion;
    }

    public static function clearPermissionsVersionCache(): void
    {
        static::$memoizedPermissionsVersion = null;
    }
}
