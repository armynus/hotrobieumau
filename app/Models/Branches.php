<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branches extends Model
{
    protected $table = 'branches';

    protected $fillable = [
        'branch_name', 'branch_code', 'branch_type', 'parent_id', 'branch_addr', 'branch_phone',
        'branch_tax_code', 'branch_tax_date', 'branch_tax_place', 'branch_general',
        'branch_fax', 'branch_place', 'database_name', 'status',
    ];

    protected $casts = [
        'parent_id' => 'integer',
    ];

    public $timestamps = true;

    public function parent()
    {
        return $this->belongsTo(Branches::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Branches::class, 'parent_id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class, 'branch_id');
    }

    public function transactionOffices()
    {
        return $this->hasMany(TransactionOffice::class, 'branch_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    // public function users()
    // {
    //     return $this->hasMany('App\Models\Users');
    // }
}
