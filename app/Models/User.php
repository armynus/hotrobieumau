<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'citizen_id',
        'email',
        'phone_no',
        'user_ipcas',
        'employee_code',
        'avatar_path',
        'password',
        'branch_id',
        'department_id',
        'transaction_office_id',
        'position_id',
        'role_id',
        'document_role',
        'status',
        'failed_login_attempts',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'avatar_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
            'branch_id' => 'integer',
            'department_id' => 'integer',
            'transaction_office_id' => 'integer',
            'position_id' => 'integer',
            'failed_login_attempts' => 'integer',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branches::class, 'branch_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function transactionOffice()
    {
        return $this->belongsTo(TransactionOffice::class, 'transaction_office_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function documentReads()
    {
        return $this->hasMany(DocumentRead::class, 'user_id');
    }

    public function formUsages()
    {
        return $this->hasMany(SupportFormUsage::class, 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id');
    }

    public function loginSessions()
    {
        return $this->hasMany(LoginSession::class, 'user_id');
    }

    public function activeLoginSessions()
    {
        return $this->hasMany(LoginSession::class, 'user_id')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    protected static array $memoizedUserVersions = [];
    protected ?array $memoizedPermissionSlugs = null;

    public static function userPermissionsVersion(int $userId): int
    {
        if (isset(static::$memoizedUserVersions[$userId])) {
            return static::$memoizedUserVersions[$userId];
        }

        $ver = (int) \Illuminate\Support\Facades\Cache::get('user_perm_version_' . $userId, 1);
        static::$memoizedUserVersions[$userId] = $ver;
        return $ver;
    }

    public static function bumpUserPermissionsVersion(int $userId): int
    {
        unset(static::$memoizedUserVersions[$userId]);
        $newVersion = (int) \Illuminate\Support\Facades\Cache::get('user_perm_version_' . $userId, 1) + 1;
        \Illuminate\Support\Facades\Cache::forever('user_perm_version_' . $userId, $newVersion);
        static::$memoizedUserVersions[$userId] = $newVersion;
        return $newVersion;
    }

    protected static array $memoizedTableExists = [];
    protected static array $memoizedRoleTablesExist = [];
    protected static array $memoizedUserRoleIdColumnExists = [];
    protected static array $memoizedPermissionsTableExists = [];
    protected static array $memoizedPermissionsStatusColumnExists = [];

    public static function hasTable(string $table, ?string $connection = null): bool
    {
        $conn = $connection ?? config('database.default', 'mysql');
        $key = $conn . ':' . $table;
        if (!isset(static::$memoizedTableExists[$key])) {
            static::$memoizedTableExists[$key] = \Illuminate\Support\Facades\Schema::connection($conn)->hasTable($table);
        }
        return static::$memoizedTableExists[$key];
    }

    public static function hasRoleTables(?string $connection = null): bool
    {
        $conn = $connection ?? config('database.default', 'mysql');
        if (!isset(static::$memoizedRoleTablesExist[$conn])) {
            static::$memoizedRoleTablesExist[$conn] = static::hasTable('roles', $conn)
                && static::hasTable('user_role', $conn);
        }
        return static::$memoizedRoleTablesExist[$conn];
    }

    public static function hasUserRoleIdColumn(?string $connection = null): bool
    {
        $conn = $connection ?? config('database.default', 'mysql');
        if (!isset(static::$memoizedUserRoleIdColumnExists[$conn])) {
            static::$memoizedUserRoleIdColumnExists[$conn] = \Illuminate\Support\Facades\Schema::connection($conn)->hasColumn('users', 'role_id');
        }
        return static::$memoizedUserRoleIdColumnExists[$conn];
    }

    public static function hasPermissionsTable(?string $connection = null): bool
    {
        return static::hasTable('permissions', $connection);
    }

    public static function hasPermissionsStatusColumn(?string $connection = null): bool
    {
        $conn = $connection ?? config('database.default', 'mysql');
        if (!isset(static::$memoizedPermissionsStatusColumnExists[$conn])) {
            static::$memoizedPermissionsStatusColumnExists[$conn] = \Illuminate\Support\Facades\Schema::connection($conn)->hasColumn('permissions', 'status');
        }
        return static::$memoizedPermissionsStatusColumnExists[$conn];
    }

    public static function clearUserPermissionsVersionCache(): void
    {
        static::$memoizedUserVersions = [];
        static::$memoizedTableExists = [];
        static::$memoizedRoleTablesExist = [];
        static::$memoizedUserRoleIdColumnExists = [];
        static::$memoizedPermissionsTableExists = [];
        static::$memoizedPermissionsStatusColumnExists = [];
    }

    /**
     * Synchronize and refresh session permissions if version mismatch is detected or cache missing.
     * Returns false if user does not exist or user is inactive/locked.
     * Returns array of fresh permissions on success.
     */
    public static function syncSessionPermissionsIfNeeded(int|string $userId, $session = null): bool|array
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return false;
        }

        $currentGlobalVersion = Role::permissionsVersion();
        $currentUserVersion = static::userPermissionsVersion($userId);

        $sessionGlobalVersion = $session ? $session->get('user_permissions_version') : session('user_permissions_version');
        $sessionUserVersion = $session ? $session->get('user_individual_version') : session('user_individual_version');
        $cachedPerms = $session ? $session->get('user_permissions') : session('user_permissions');

        if (
            is_array($cachedPerms)
            && $sessionGlobalVersion !== null
            && $sessionGlobalVersion === $currentGlobalVersion
            && $sessionUserVersion !== null
            && $sessionUserVersion === $currentUserVersion
        ) {
            return $cachedPerms;
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
            return is_array($cachedPerms) ? $cachedPerms : [];
        }

        $user = static::find($userId);
        if (!$user) {
            return is_array($cachedPerms) ? $cachedPerms : false;
        }

        if (($user->status ?? 'active') !== 'active') {
            return false;
        }

        $perms = $user->getAllPermissionSlugs(true);
        $roleSlugs = static::hasRoleTables()
            ? $user->roles()->where('status', 'active')->pluck('slug')->toArray()
            : [];

        $sessionData = [
            'user_role' => $user->role_id,
            'user_document_role' => $user->document_role,
            'user_roles' => $roleSlugs,
            'user_permissions' => $perms,
            'user_permissions_version' => $currentGlobalVersion,
            'user_individual_version' => $currentUserVersion,
        ];

        if ($session) {
            foreach ($sessionData as $key => $val) {
                $session->put($key, $val);
            }
        } else {
            session($sessionData);
        }

        if (app()->bound('request')) {
            $req = request();
            if ($req->attributes->has('memoized_auth_user')) {
                $req->attributes->set('memoized_auth_user', $user);
            }
        }

        return $perms;
    }

    public function isClerk(): bool
    {
        if ($this->document_role === 'clerk') {
            return true;
        }

        if (!static::hasRoleTables()) {
            return false;
        }

        return $this->roles->contains(function ($role) {
            return ($role->status ?? 'active') === 'active' && $role->slug === 'clerk';
        });
    }

    public function canUploadDocument(): bool
    {
        // If the feature is inactive system-wide, disallow uploading
        if (class_exists(\App\Models\Permission::class) && !\App\Models\Permission::isFeatureActive('documents.upload')) {
            return false;
        }

        // Chỉ Văn thư thuộc Chi nhánh loại 1 mới được phép đăng tải
        return ($this->isClerk() || $this->hasPermission('documents.upload')) && $this->branch && $this->branch->branch_type === 'type_1';
    }

    public function isDocumentManager(): bool
    {
        return $this->isClerk() || in_array($this->position?->level, [1, 2]); // Giám đốc, PGĐ
    }

    /**
     * Check if user is a leadership role (Not a regular employee)
     */
    public function isLeadership(): bool
    {
        if (! $this->position_id || ! $this->position || ! in_array((int) $this->position->level, [1, 2, 3, 4, 5], true)) {
            return false;
        }

        // Exclude positions named like 'Nhân viên' or 'Cán bộ' if needed
        $posName = mb_strtolower($this->position->position_name ?? '', 'UTF-8');
        if (str_contains($posName, 'nhân viên') || str_contains($posName, 'chuyên viên')) {
            return false;
        }

        return true;
    }

    /**
     * Check if user has specific role by slug or array of slugs
     */
    public function hasRole(string|array $roles): bool
    {
        if ((int) $this->role_id === 0) {
            return true;
        }

        $roleSlugs = (array) $roles;

        if (!static::hasRoleTables()) {
            if ((int) $this->role_id === 1 && in_array('supervisor', $roleSlugs, true)) return true;
            if ((int) $this->role_id === 2 && in_array('staff', $roleSlugs, true)) return true;
            if ($this->document_role === 'clerk' && in_array('clerk', $roleSlugs, true)) return true;
            return false;
        }

        return $this->roles->contains(function ($role) use ($roleSlugs) {
            return ($role->status ?? 'active') === 'active' && in_array($role->slug, $roleSlugs, true);
        });
    }

    /**
     * Check if user has specific permission by slug
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // 1. If feature/permission is inactive on system, block access
        if (!Permission::isFeatureActive($permissionSlug)) {
            return false;
        }

        if (isset($this->attributes['role_id']) && (int) $this->role_id === 0) {
            return true;
        }

        $allPerms = $this->getAllPermissionSlugs();
        return in_array('*', $allPerms, true) || in_array($permissionSlug, $allPerms, true);
    }

    /**
     * Get all permission slugs for this user across all assigned roles
     */
    public function getAllPermissionSlugs(bool $forceRefresh = false): array
    {
        if (!$forceRefresh && isset($this->memoizedPermissionSlugs)) {
            return $this->memoizedPermissionSlugs;
        }

        if (!static::hasUserRoleIdColumn()) {
            return ['*'];
        }

        if (isset($this->attributes['role_id']) && (int) $this->role_id === 0) {
            if (!static::hasPermissionsTable()) {
                return ['*'];
            }
            $query = Permission::query();
            if (static::hasPermissionsStatusColumn()) {
                $query->where('status', 'active');
            }
            $this->memoizedPermissionSlugs = $query->pluck('slug')->toArray();
            return $this->memoizedPermissionSlugs;
        }

        $permissions = [];
        $hasRoleSystem = static::hasRoleTables();
        $hasUserRoleAssigned = false;

        if ($hasRoleSystem) {
            $roles = $this->roles()->where('status', 'active')->with(['permissions' => function ($q) {
                if (static::hasPermissionsStatusColumn()) {
                    $q->where('status', 'active');
                }
            }])->get();
            $hasUserRoleAssigned = $roles->isNotEmpty();
            foreach ($roles as $role) {
                foreach ($role->permissions as $perm) {
                    if (($perm->status ?? 'active') === 'active') {
                        $permissions[] = $perm->slug;
                    }
                }
            }
        }

        // Backward compatibility fallback ONLY for legacy rows without user_role records
        if (empty($permissions) && ! $hasUserRoleAssigned) {
            if ((int) $this->role_id === 1) {
                $permissions = array_merge($permissions, [
                    'documents.view', 'documents.reports',
                    'forms.view', 'forms.print', 'forms.bundle',
                    'it_support.request', 'it_support.templates',
                    'data.customers.view', 'data.customers.upload',
                    'data.accounts.view', 'data.accounts.upload',
                    'data.merger_lookup', 'data.scan_qr',
                ]);
            } else {
                $permissions = array_merge($permissions, [
                    'documents.view',
                    'forms.view', 'forms.print', 'forms.bundle',
                    'it_support.request', 'it_support.templates',
                    'data.customers.view', 'data.accounts.view',
                    'data.merger_lookup',
                ]);
            }

            if ($this->document_role === 'clerk') {
                $permissions = array_merge($permissions, [
                    'documents.view', 'documents.upload', 'documents.manage_ledger', 'documents.distribute', 'documents.reports',
                ]);
            }
        }

        $this->memoizedPermissionSlugs = array_values(array_unique($permissions));
        return $this->memoizedPermissionSlugs;
    }
}
