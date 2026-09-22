<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataImport extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'type',
        'tenant_database',
        'original_name',
        'disk',
        'path',
        'status',
        'total_rows',
        'processed_rows',
        'inserted_rows',
        'updated_rows',
        'skipped_rows',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed'], true);
    }
}
