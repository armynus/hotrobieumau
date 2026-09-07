<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;

class DocumentQueryService
{
    /**
     * Get documents that the user is allowed to see.
     * This applies branch, department, position and specific user permissions.
     */
    public function getDocumentsForUser($user, $filters = [])
    {
        $query = Document::with(['documentType', 'creator']);

        // Apply Permission Logic
        $query->where(function (Builder $q) use ($user) {
            // Rule 0: Public Documents
            $q->where('visibility', Document::VISIBILITY_SYSTEM);

            // Rule 0.1: Public within Branch
            $q->orWhere(function (Builder $qb) use ($user) {
                $qb->where('visibility', Document::VISIBILITY_BRANCH)
                    ->where('managing_branch_id', $user->branch_id);
            });

            $q->orWhere(function (Builder $sub) use ($user) {
                if ($user->isClerk()) {
                    // Clerks can see documents in their branch or transferred to their branch
                    $sub->where('managing_branch_id', $user->branch_id)
                    // Or documents transferred to their branch
                        ->orWhereHas('transfers', function ($qt) use ($user) {
                            $qt->where('to_branch_id', $user->branch_id);
                        });
                } else {
                    // Normal users / Managers: check permissions
                    $sub->where(function (Builder $qNormal) use ($user) {
                        // Rule 1: Explicit permissions (Legacy or specific share)
                        $qNormal->whereHas('permissions', function (Builder $qp) use ($user) {
                            $qp->where(function ($s) use ($user) {
                                $s->where('target_type', 'user')->where('target_id', $user->id);
                            })
                                ->orWhere(function ($s) use ($user) {
                                    $s->where('target_type', 'position')->where('target_id', $user->position_id);
                                })
                                ->orWhere(function ($s) use ($user) {
                                    $s->where('target_type', 'department')->where('target_id', $user->department_id);
                                })
                                ->orWhere(function ($s) use ($user) {
                                    $s->where('target_type', 'branch')->where('target_id', $user->branch_id);
                                });
                        });

                        // Rule 2: Automatic Leadership Permission
                        if ($user->isLeadership()) {
                            $qNormal->orWhere('managing_branch_id', $user->branch_id)
                                ->orWhereHas('transfers', function ($qt) use ($user) {
                                    $qt->where('to_branch_id', $user->branch_id);
                                });
                        }
                    });
                }
            });
        });

        // Apply Filters
        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (! empty($filters['document_type_id'])) {
            $query->where('document_type_id', $filters['document_type_id']);
        }

        if (! empty($filters['keyword'])) {
            $keyword = '%'.$filters['keyword'].'%';
            $query->where('document_code', 'like', $keyword);
        }

        $dateColumn = match ($filters['direction'] ?? null) {
            Document::DIRECTION_INCOMING => 'received_date',
            Document::DIRECTION_OUTGOING => 'forwarded_date',
            default => 'issued_date',
        };

        if (! empty($filters['date_from'])) {
            $query->where(function ($dateQuery) use ($dateColumn, $filters) {
                $dateQuery->whereDate($dateColumn, '>=', $filters['date_from']);
                if ($dateColumn !== 'issued_date') {
                    $dateQuery->orWhere(function ($fallback) use ($dateColumn, $filters) {
                        $fallback->whereNull($dateColumn)
                            ->whereDate('issued_date', '>=', $filters['date_from']);
                    });
                }
            });
        }

        if (! empty($filters['date_to'])) {
            $query->where(function ($dateQuery) use ($dateColumn, $filters) {
                $dateQuery->whereDate($dateColumn, '<=', $filters['date_to']);
                if ($dateColumn !== 'issued_date') {
                    $dateQuery->orWhere(function ($fallback) use ($dateColumn, $filters) {
                        $fallback->whereNull($dateColumn)
                            ->whereDate('issued_date', '<=', $filters['date_to']);
                    });
                }
            });
        }

        if (! empty($filters['issued_date_from'])) {
            $query->whereDate('issued_date', '>=', $filters['issued_date_from']);
        }

        if (! empty($filters['issued_date_to'])) {
            $query->whereDate('issued_date', '<=', $filters['issued_date_to']);
        }

        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            if ($filters['is_read']) {
                $query->whereHas('reads', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } else {
                $query->whereDoesntHave('reads', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
        }

        // Văn bản nhập từ kho lưu trữ cũ vẫn tra cứu bình thường nhưng không được
        // xem là "văn bản mới" trong khu vực thông báo của người dùng.
        if (! empty($filters['exclude_archive_imports'])) {
            $query->whereDoesntHave('logs', function ($q) {
                $q->where('action', 'archive_imported');
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $allowedSorts = ['created_at', 'direction', 'issued_date', 'received_date', 'forwarded_date', 'title', 'document_code', 'registry_number', 'issuing_agency', 'signer', 'recipient'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id');

        return $query;
    }
}
