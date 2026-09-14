<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'user_ipcas', 'password', 'branch_id', 'status', 'role_id', 'failed_login_attempts',
        // Form quản trị gửi các trường này khi tạo user; không được bỏ qua chức vụ/văn thư.
        'department_id', 'position_id', 'document_role',
    ];

    public $timestamps = true;

    public function formUsages()
    {
        return $this->hasMany(SupportFormUsage::class, 'user_id');
    }
}
