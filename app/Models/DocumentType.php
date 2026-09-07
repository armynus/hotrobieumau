<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Document;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'document_type_id');
    }
}
