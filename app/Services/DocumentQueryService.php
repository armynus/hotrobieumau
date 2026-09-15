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
        if (! $user->branch_id) {
            return $query->whereRaw('1 = 0');
        }

        // Không có quyền xem toàn hệ thống/chi nhánh ngầm theo mức công khai.
        $query->where(function (Builder $scope) use ($user) {
            if ($user->isClerk()) {
                $scope->where('managing_branch_id', $user->branch_id)
                    ->orWhere(function ($received) use ($user) {
                        $received->whereIn('visibility', [Document::VISIBILITY_NORMAL, Document::VISIBILITY_PUBLIC])
                            ->whereHas('activeTransfers', fn ($transfer) => $transfer->where('to_branch_id', $user->branch_id));
                    });

                return;
            }
            $scope->where(function ($direct) use ($user) {
                // Ban giám đốc chỉ được đọc khi chọn đích danh; riêng tư chỉ cùng CN.
                if (! in_array((int) $user->position?->level, [1, 2], true)) {
                    $direct->whereRaw('1 = 0');

                    return;
                }
                $direct->whereHas('permissions', fn ($permission) => $permission
                    ->where('target_type', 'user')->where('target_id', $user->id))
                    ->where(function ($branch) use ($user) {
                        $branch->where('managing_branch_id', $user->branch_id)
                            ->orWhere(function ($received) use ($user) {
                                $received->whereIn('visibility', [Document::VISIBILITY_NORMAL, Document::VISIBILITY_PUBLIC])
                                    ->whereHas('activeTransfers', fn ($transfer) => $transfer->where('to_branch_id', $user->branch_id));
                            });
                    });
            })->orWhere(function ($department) use ($user) {
                if (! $user->department_id) {
                    $department->whereRaw('1 = 0');

                    return;
                }
                $department->whereIn('visibility', $user->isLeadership()
                    ? [Document::VISIBILITY_NORMAL, Document::VISIBILITY_PUBLIC]
                    : [Document::VISIBILITY_PUBLIC])
                    ->whereHas('permissions', fn ($permission) => $permission
                        ->where('target_type', 'department')->where('target_id', $user->department_id))
                    ->where(function ($branch) use ($user) {
                        $branch->where('managing_branch_id', $user->branch_id)
                            ->orWhereHas('activeTransfers', fn ($transfer) => $transfer->where('to_branch_id', $user->branch_id));
                    })
                    ->whereExists(function ($assigned) use ($user) {
                        $assigned->selectRaw('1')->from('departments')
                            ->where('departments.id', $user->department_id)->where('departments.branch_id', $user->branch_id);
                    });
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
            Document::DIRECTION_OUTGOING, Document::DIRECTION_DECISION => 'forwarded_date',
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
            $query->where(function ($scope) {
                $scope->whereDoesntHave('logs', fn ($q) => $q->whereIn('action', ['archive_imported', 'ledger_imported', 'ledger_recorded']))
                    ->orWhereHas('logs', fn ($q) => $q->where('action', 'published'));
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $allowedSorts = ['id', 'created_at', 'direction', 'issued_date', 'received_date', 'forwarded_date', 'title', 'document_code', 'registry_number', 'issuing_agency', 'signer', 'recipient'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        if (in_array($sortBy, ['issued_date', 'received_date', 'forwarded_date'], true)) {
            $query->orderByRaw($sortBy.' IS NULL ASC');
        }
        $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        if ($sortBy !== 'id') {
            $query->orderByDesc('id');
        }

        return $query;
    }
}
