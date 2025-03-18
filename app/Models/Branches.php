<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branches extends Model
{
    protected $table = 'branches';
    protected $fillable = ['branch_name','branch_code','branch_addr','branch_phone','branch_fax','branch_place','database_name','status'];
    public $timestamps = true;
    // public function users()
    // {
    //     return $this->hasMany('App\Models\Users');
    // }
}
