<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentExportOperation extends Model
{
    protected $table = 'document_exports';

    protected $fillable = [
        'user_id',
        'branch_id',
        'direction',
        'period_type',
        'year',
        'month',
        'quarter',
        'period_label',
        'file_name',
        'disk',
        'path',
        'status',
        'row_count',
        'error_message',
        'started_at',
        'finished_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }
}
