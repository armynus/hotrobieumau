<?php

namespace App\Services;

use App\Models\Branches;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentLog;
use App\Models\DocumentTransfer;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentRecipientService
{
    public function directors(int $branchId): Collection
    {
        return User::where('branch_id', $branchId)
            ->whereHas('position', fn ($query) => $query->whereIn('level', [1, 2]))
            ->with('position:id,position_name')
            ->orderBy('name')->get(['id', 'name', 'branch_id', 'position_id']);
    }

    public function validateBranches(array $branchIds): void
    {
        $validBranches = Branches::where('branch_type', 'type_2')->where('status', 'active')
            ->whereIn('id', $branchIds)->pluck('id');
        if (collect($branchIds)->diff($validBranches)->isNotEmpty()) {
            throw ValidationException::withMessages(['to_branch_ids' => 'Chỉ được chọn chi nhánh loại II đang hoạt động.']);
        }
    }

    public function validateTargets(User $sender, array $users, array $departments): void
    {
        $validUsers = $this->directors((int) $sender->branch_id)->pluck('id');
        $validDepartments = Department::where('branch_id', $sender->branch_id)
            ->where('status', 'active')->pluck('id');
        if (collect($users)->diff($validUsers)->isNotEmpty() || collect($departments)->diff($validDepartments)->isNotEmpty()) {
            throw ValidationException::withMessages(['recipients' => 'Chỉ được chọn ban giám đốc và phòng ban thuộc chi nhánh của bạn.']);
        }
    }

    public function validateVisibility(string $visibility, array $departments, array $branches = []): void
    {
        if ($visibility === Document::VISIBILITY_PRIVATE && ($departments !== [] || $branches !== [])) {
            throw ValidationException::withMessages(['recipients' => 'Riêng Tư chỉ cho phép chọn ban giám đốc cùng chi nhánh, không gửi phòng ban hoặc chi nhánh cấp dưới.']);
        }
    }

    public function distribute(Document $document, User $sender, array $users, array $departments, ?string $note = null): void
    {
        if (! $document->canBeDistributedToDepartmentBy($sender)) {
            throw new AuthorizationException('Bạn chỉ được phân phối văn bản do mình đăng tải hoặc chi nhánh đã nhận.');
        }
        $this->validateTargets($sender, $users, $departments);
        $this->validateVisibility($document->visibility, $departments);
        DB::transaction(function () use ($document, $sender, $users, $departments, $note) {
            // Serialize with edits so a revoked branch cannot grant access concurrently.
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            if (! $document->canBeDistributedToDepartmentBy($sender)) {
                throw new AuthorizationException('Quyền phân phối văn bản đã thay đổi. Vui lòng tải lại.');
            }
            $this->validateVisibility($document->visibility, $departments);
            $service = app(DocumentService::class);
            foreach (array_unique($departments) as $departmentId) {
                $service->transferDocumentToDepartment($document, (int) $departmentId, $sender, $note);
            }
            foreach (array_unique($users) as $userId) {
                DocumentTransfer::updateOrCreate([
                    'document_id' => $document->id,
                    'from_branch_id' => $sender->branch_id,
                    'to_branch_id' => null,
                    'to_department_id' => null,
                    'to_user_id' => $userId,
                ], ['transferred_by' => $sender->id, 'transferred_at' => now(), 'status' => 'received', 'note' => $note]);
                $service->assignPermission($document, 'user', (int) $userId, $sender);
                DocumentLog::create([
                    'document_id' => $document->id, 'user_id' => $sender->id,
                    'action' => 'distributed_to_director',
                    'details' => ['to_user_id' => $userId, 'note' => $note],
                ]);
            }
        });
    }

    /** Current uploader-branch choices; downstream choices remain managed by their clerks. */
    public function selection(Document $document): array
    {
        $permissions = $document->permissions()->get(['target_type', 'target_id']);
        $localUsers = User::where('branch_id', $document->managing_branch_id)->pluck('id');
        $localDepartments = Department::where('branch_id', $document->managing_branch_id)->pluck('id');

        return [
            'to_user_ids' => $permissions->where('target_type', 'user')->pluck('target_id')->intersect($localUsers)->values()->all(),
            'to_department_ids' => $permissions->where('target_type', 'department')->pluck('target_id')->intersect($localDepartments)->values()->all(),
            'to_branch_ids' => $document->activeTransfers()->where('from_branch_id', $document->managing_branch_id)
                ->whereNotNull('to_branch_id')->pluck('to_branch_id')->unique()->values()->all(),
        ];
    }

    /** Replace explicit choices atomically; retain audit history, revoke downstream access. */
    public function sync(Document $document, User $sender, array $users, array $departments, array $branches): void
    {
        DB::transaction(function () use ($document, $sender, $users, $departments, $branches) {
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            if (! $document->canBeEditedBy($sender)) {
                throw new AuthorizationException('Chỉ văn thư đã đăng tải được sửa phạm vi xem.');
            }
            $this->validateTargets($sender, $users, $departments);
            $this->validateBranches($branches);
            $this->validateVisibility($document->visibility, $departments, $branches);
            $before = $this->selection($document);

            // Local unchecked recipients and every recipient in a removed branch lose access.
            $allowedBranches = array_merge([(int) $sender->branch_id], $branches);
            foreach (['user' => User::class, 'department' => Department::class] as $type => $model) {
                $selected = $type === 'user' ? $users : $departments;
                $revokedIds = $model::where(function ($query) use ($sender, $allowedBranches, $selected) {
                    $query->whereNotIn('branch_id', $allowedBranches)
                        ->orWhere(function ($local) use ($sender, $selected) {
                            $local->where('branch_id', $sender->branch_id)->whereNotIn('id', $selected);
                        });
                })->pluck('id');
                $document->permissions()->where('target_type', $type)->whereIn('target_id', $revokedIds)->delete();
                $document->activeTransfers()->whereIn('to_'.$type.'_id', $revokedIds)->update(['status' => 'revoked']);
            }
            $document->activeTransfers()->where(function ($query) use ($branches, $allowedBranches) {
                $query->where(function ($branch) use ($branches) {
                    $branch->whereNotNull('to_branch_id')->whereNotIn('to_branch_id', $branches);
                })->orWhereNotIn('from_branch_id', $allowedBranches);
            })->update(['status' => 'revoked']);

            $addedUsers = array_values(array_diff($users, $before['to_user_ids']));
            $addedDepartments = array_values(array_diff($departments, $before['to_department_ids']));
            if ($addedUsers || $addedDepartments) {
                $this->distribute($document, $sender, $addedUsers, $addedDepartments);
            }
            foreach (array_diff($branches, $before['to_branch_ids']) as $branchId) {
                app(DocumentService::class)->transferDocument($document, (int) $sender->branch_id, (int) $branchId, $sender);
            }
            $after = $this->selection($document);
            if ($before != $after) {
                DocumentLog::create([
                    'document_id' => $document->id, 'user_id' => $sender->id,
                    'action' => 'distribution_updated', 'details' => ['before' => $before, 'after' => $after],
                ]);
            }
        });
    }
}
