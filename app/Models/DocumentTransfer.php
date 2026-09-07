<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'from_branch_id',
        'to_branch_id',
        'to_department_id',
        'to_user_id',
        'transferred_by',
        'transferred_at',
        'note',
        'status',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branches::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branches::class, 'to_branch_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function transferer()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
