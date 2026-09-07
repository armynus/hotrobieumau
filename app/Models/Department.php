<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'department_name',
        'department_code',
        'parent_id',
        'status',
    ];

    public function branch()
    {
        return $this->belongsTo(Branches::class, 'branch_id');
    }

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
