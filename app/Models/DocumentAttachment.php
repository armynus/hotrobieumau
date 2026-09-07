<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'file_path',
        'file_name',
        'file_extension',
        'mime_type',
        'file_size',
        'uploaded_by',
        'archive_source_key',
        'archive_relative_path',
        'checksum_sha256',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
