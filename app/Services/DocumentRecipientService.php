<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Branches;
use App\Models\Document;
use App\Models\DocumentLog;
use App\Models\DocumentTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
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

    public function distribute(Document $document, User $sender, array $users, array $departments, ?string $note = null): void
    {
        if (! $document->canBeDistributedToDepartmentBy($sender)) {
            throw new AuthorizationException('Bạn chỉ được phân phối văn bản do mình đăng tải hoặc chi nhánh đã nhận.');
        }
        $this->validateTargets($sender, $users, $departments);
        DB::transaction(function () use ($document, $sender, $users, $departments, $note) {
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
}
