<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'user_ipcas', 'avatar_path', 'password', 'branch_id', 'status', 'role_id', 'failed_login_attempts',
        // Form quản trị gửi các trường này khi tạo user; không được bỏ qua chức vụ/văn thư.
        'department_id', 'transaction_office_id', 'position_id', 'document_role',
    ];

    public $timestamps = true;

    protected $hidden = ['password', 'remember_token', 'avatar_path'];

    protected $casts = [
        'branch_id' => 'integer',
        'department_id' => 'integer',
        'transaction_office_id' => 'integer',
        'position_id' => 'integer',
        'failed_login_attempts' => 'integer',
    ];

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

    public function formUsages()
    {
        return $this->hasMany(SupportFormUsage::class, 'user_id');
    }
}
