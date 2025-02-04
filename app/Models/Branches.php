<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branches extends Model
{
    protected $table = 'branches';
    protected $fillable = ['branch_name','database_name','status'];
    public $timestamps = true;
    // public function users()
    // {
    //     return $this->hasMany('App\Models\Users');
    // }
}
