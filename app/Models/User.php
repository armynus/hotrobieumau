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
        'email',
        'password',
        'branch_id',
        'department_id',
        'position_id',
        'role_id',
        'document_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
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

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function documentReads()
    {
        return $this->hasMany(DocumentRead::class, 'user_id');
    }

    public function isClerk(): bool
    {
        return $this->document_role === 'clerk';
    }

    public function canUploadDocument(): bool
    {
        // Chỉ Văn thư thuộc Chi nhánh loại 1 mới được phép đăng tải
        return $this->isClerk() && $this->branch && $this->branch->branch_type === 'type_1';
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
        if (!$this->position_id) {
            return false;
        }
        
        // Exclude positions named like 'Nhân viên' or 'Cán bộ' if needed
        $posName = mb_strtolower($this->position->position_name ?? '', 'UTF-8');
        if (str_contains($posName, 'nhân viên') || str_contains($posName, 'chuyên viên')) {
            return false;
        }

        return true;
    }
}
