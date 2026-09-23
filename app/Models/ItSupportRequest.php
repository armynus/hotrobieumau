<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItSupportRequest extends Model
{
    use HasFactory;

    protected $table = 'it_support_requests';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'contact_phone',
        'status',
        'resolution_note',
        'completed_at',
        'attachment_paths',
    ];

    protected $casts = [
        'attachment_paths' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function events()
    {
        return $this->hasMany(ItSupportEvent::class, 'request_id')->orderBy('created_at')->orderBy('id');
    }

    public function attachmentFiles(): array
    {
        return collect($this->attachment_paths ?: [])->map(fn ($item) => is_string($item)
            ? ['path' => $item, 'name' => basename($item)]
            : ['path' => $item['path'] ?? '', 'name' => $item['name'] ?? basename($item['path'] ?? '')])
            ->filter(fn ($item) => $item['path'] !== '')->values()->all();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Chờ tiếp nhận',
            'processing' => 'Đang xử lý',
            'resolved' => 'Đã hoàn thành',
            'closed' => 'Đã đóng',
            default => 'Chưa rõ',
        };
    }
}
