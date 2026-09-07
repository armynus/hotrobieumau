<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'target_type',
        'target_id',
        'permission',
        'granted_by',
        'granted_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function granter()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
