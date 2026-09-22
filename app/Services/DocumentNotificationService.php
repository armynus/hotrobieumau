<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class DocumentNotificationService
{
    public function __construct(private readonly DocumentQueryService $documents) {}

    /**
     * @return array{unread_count:int, items:array<int, array{id:int, document_code:string, date:string, detail_url:string}>}
     */
    public function unread(User $user, int $limit = 5): array
    {
        $query = $this->documents->getDocumentsForUser($user, [
            'is_read' => false,
            'exclude_archive_imports' => true,
            'skip_sort' => true,
        ]);

        $unreadCount = (clone $query)->count();
        $documents = $query
            ->select(['id', 'document_code', 'issued_date', 'created_at'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(max(1, min($limit, 20)))
            ->get();

        return [
            'unread_count' => $unreadCount,
            'items' => $documents->map(fn ($document) => [
                'id' => (int) $document->id,
                'document_code' => Str::limit($document->document_code ?: 'Chưa cập nhật số, ký hiệu', 40),
                'date' => ($document->issued_date ?? $document->created_at)?->format('d/m/Y') ?? '',
                'detail_url' => route('document_detail', $document->id),
            ])->all(),
        ];
    }
}
