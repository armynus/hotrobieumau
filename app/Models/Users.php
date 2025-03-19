<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password', 'branch_id', 'status', 'role_id', 'failed_login_attempts'];
    public $timestamps = true;
    // public function branches()
    // {
    //     return $this->belongsTo('App\Models\Branches');
    // }
}
