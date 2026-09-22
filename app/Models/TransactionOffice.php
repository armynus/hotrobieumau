<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionOffice extends Model
{
    protected $fillable = [
        'branch_id',
        'office_name',
        'office_code',
        'office_address',
        'office_place',
        'office_phone',
        'office_fax',
        'office_email',
        'manager_name',
        'status',
    ];

    protected $casts = [
        'branch_id' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branches::class, 'branch_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'transaction_office_id');
    }
}
