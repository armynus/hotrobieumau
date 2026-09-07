<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Branches;
use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentQueryService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DocumentApiController extends Controller
{
    protected $documentService;

    protected $documentQueryService;

    public function __construct(DocumentService $documentService, DocumentQueryService $documentQueryService)
    {
        $this->documentService = $documentService;
        $this->documentQueryService = $documentQueryService;
    }

    /**
     * Get list of documents for the logged in user
     */
    public function index(Request $request)
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = User::with(['position', 'branch'])->find($userId);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $filters = $request->only([
            'direction', 'document_type_id', 'keyword', 'date_from', 'date_to',
            'issued_date_from', 'issued_date_to', 'is_read',
        ]);
        $filters['keyword'] = ($filters['keyword'] ?? null) ?: $request->input('search.value');
        if (($filters['is_read'] ?? '') === '0') {
            $filters['exclude_archive_imports'] = true;
        }

        $orderColumn = (int) $request->input('order.0.column', 0);
        $requestedSort = $request->input("columns.{$orderColumn}.name");
        $allowedSorts = [
            'direction', 'title', 'document_code', 'registry_number', 'issued_date', 'received_date',
            'forwarded_date', 'issuing_agency', 'signer', 'recipient', 'created_at',
        ];
        $filters['sort_by'] = in_array($requestedSort, $allowedSorts, true)
            ? $requestedSort
            : match ($filters['direction'] ?? null) {
                Document::DIRECTION_INCOMING => 'received_date',
                Document::DIRECTION_OUTGOING => 'forwarded_date',
                default => 'issued_date',
            };
        $filters['sort_dir'] = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        // Tổng số văn bản người dùng được phép xem, chưa áp dụng bộ lọc tra cứu.
        $totalFilters = array_filter([
            'direction' => $filters['direction'] ?? null,
        ]);
        $recordsTotal = $this->documentQueryService->getDocumentsForUser($user, $totalFilters)->count();

        $query = $this->documentQueryService
            ->getDocumentsForUser($user, $filters)
            ->with('attachments:id,document_id,file_path,file_name');

        $isTypeTwoClerk = $user->isClerk() && $user->branch?->branch_type === 'type_2';
        if ($isTypeTwoClerk) {
            $query->withExists([
                'transfers as has_incoming_branch_transfer' => fn ($transferQuery) => $transferQuery
                    ->where('to_branch_id', $user->branch_id),
            ]);
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $requestedLength = (int) $request->input('length', 15);
        $length = $requestedLength === -1 ? min($recordsFiltered, 1000) : max(1, min($requestedLength, 100));
        $documents = $query->skip($start)->take($length)->get();

        $documents->each(function (Document $document) use ($user, $isTypeTwoClerk) {
            $canEdit = $document->canBeEditedBy($user);
            $canDistribute = $isTypeTwoClerk && (bool) $document->getAttribute('has_incoming_branch_transfer');

            $document->setAttribute('capabilities', [
                'can_edit' => $canEdit,
                'can_delete' => $document->canBeDeletedBy($user),
                'can_transfer' => $canEdit || $canDistribute,
                'transfer_target_type' => $canEdit ? 'branch' : ($canDistribute ? 'department' : null),
            ]);
            $document->makeHidden('has_incoming_branch_transfer');
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'success' => true,
            'data' => $documents,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
        ]);
    }

    /**
     * Store a new document (Registration)
     */
    public function store(Request $request)
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = User::with('branch')->find($userId);
        if (! $user || ! $user->canUploadDocument()) {
            return response()->json(['success' => false, 'message' => 'Forbidden: Only Clerk in Type 1 Branch can upload documents'], 403);
        }

        // Validate
        $validated = $request->validate([
            'direction' => 'required|in:incoming,outgoing',
            'title' => 'required|string|max:5000',
            'registry_number' => 'required_if:direction,incoming|nullable|string|max:255',
            'document_code' => 'required|string|max:255',
            'document_type_id' => 'nullable|integer|exists:document_types,id',
            'issued_date' => 'nullable|date',
            'received_date' => 'required_if:direction,incoming|nullable|date',
            'forwarded_date' => 'required_if:direction,outgoing|nullable|date',
            'issuing_agency' => 'required_if:direction,incoming|nullable|string|max:255',
            'signer' => 'required_if:direction,outgoing|nullable|string|max:255',
            'recipient' => 'nullable|string|max:5000',
            'archive_recipient' => 'nullable|string|max:5000',
            'copy_count' => 'nullable|integer|min:1|max:100000',
            'receipt_signature' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'priority' => 'nullable|in:normal,urgent,very_urgent',
            'security_level' => 'nullable|in:normal,confidential,secret,top_secret',
            'files' => 'required|array',
            'files.*' => 'file|max:51200', // 50MB max per file
            'is_public_level' => 'required|in:0,1,2',
            'to_branch_ids' => 'nullable|array',
            'to_branch_ids.*' => 'integer|distinct',
        ]);

        try {
            DB::beginTransaction();

            $visibility = match ((int) $validated['is_public_level']) {
                2 => Document::VISIBILITY_SYSTEM,
                1 => Document::VISIBILITY_BRANCH,
                default => Document::VISIBILITY_PRIVATE,
            };

            $data = $request->only([
                'direction', 'registry_number', 'document_code', 'title',
                'document_type_id', 'issued_date', 'received_date', 'forwarded_date',
                'issuing_agency', 'signer', 'recipient', 'archive_recipient', 'copy_count',
                'receipt_signature', 'notes', 'priority', 'security_level',
            ]);
            $data['visibility'] = $visibility;
            $data['managing_branch_id'] = $user->branch_id;

            $createResult = $this->documentService->createDocument($data, $request->file('files'), $user);
            $document = $createResult['document'];
            $renamedFiles = $createResult['renamed_files'];

            // Forward logic
            if ($visibility !== Document::VISIBILITY_SYSTEM) {
                // Determine implicit recipients based on level (0 = leadership, 1 = branch)
                // We'll skip the logic here and rely on the DocumentQueryService rules instead.

                // If user selected explicit branches to forward to
                if ($request->has('to_branch_ids')) {
                    $branchIds = $request->input('to_branch_ids');
                    if (is_array($branchIds)) {
                        foreach ($branchIds as $branchId) {
                            if ($branchId != 'ALL' && is_numeric($branchId)) {
                                $this->documentService->transferDocument($document, $user->branch_id, $branchId, $user);
                            }
                        }
                    }
                }
            }

            DB::commit();

            $typeLabel = $data['direction'] === Document::DIRECTION_OUTGOING ? 'đi' : 'đến';
            $message = 'Văn bản '.$typeLabel.' đã được đăng tải thành công!';
            if (! empty($renamedFiles)) {
                $storedNames = array_column($renamedFiles, 'stored');
                $message .= ' File trùng tên được lưu thành: '.implode(', ', $storedNames).'.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $document,
                'renamed_files' => $renamedFiles,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific document
     */
    public function show($id)
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = User::with('branch')->find($userId);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $document = $this->documentQueryService->getDocumentsForUser($user)
            ->with([
                'attachments',
                'creator:id,name',
                'logs' => fn ($query) => $query->with('user:id,name')->latest(),
                'transfers' => fn ($query) => $query
                    ->with(['toBranch:id,branch_name', 'toDepartment:id,department_name', 'transferer:id,name'])
                    ->latest('transferred_at'),
            ])
            ->find($id);

        if (! $document) {
            return response()->json(['success' => false, 'message' => 'Not found or forbidden'], 404);
        }

        // Mark as read
        $this->documentService->markAsRead($document, $user);

        return response()->json([
            'success' => true,
            'data' => $document,
            'capabilities' => [
                'can_edit' => $document->canBeEditedBy($user),
                'can_transfer_to_branch' => $document->canBeTransferredToBranchBy($user),
                'can_distribute_to_department' => $document->canBeDistributedToDepartmentBy($user),
            ],
        ]);
    }

    /**
     * Update a document. Only the type-I clerk who uploaded it may edit it.
     */
    public function update(Request $request, $id)
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = User::with('branch')->find($userId);
        $document = Document::find($id);

        if (! $user || ! $document || ! $document->canBeEditedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ văn thư Chi nhánh loại I đã đăng tải văn bản mới được chỉnh sửa.',
            ], 403);
        }

        $direction = $document->direction ?: Document::DIRECTION_INCOMING;
        $incomingRequired = $direction === Document::DIRECTION_INCOMING ? 'required' : 'nullable';
        $outgoingRequired = $direction === Document::DIRECTION_OUTGOING ? 'required' : 'nullable';

        $validated = $request->validate([
            'direction' => 'nullable|in:incoming,outgoing',
            'title' => 'required|string|max:5000',
            'registry_number' => $incomingRequired.'|string|max:255',
            'document_code' => 'required|string|max:255',
            'document_type_id' => 'nullable|integer|exists:document_types,id',
            'issued_date' => 'nullable|date',
            'received_date' => $incomingRequired.'|date',
            'forwarded_date' => $outgoingRequired.'|date',
            'issuing_agency' => $incomingRequired.'|string|max:255',
            'signer' => $outgoingRequired.'|string|max:255',
            'recipient' => 'nullable|string|max:5000',
            'archive_recipient' => 'nullable|string|max:5000',
            'copy_count' => 'nullable|integer|min:1|max:100000',
            'receipt_signature' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'priority' => 'required|in:normal,urgent,very_urgent',
            'security_level' => 'required|in:normal,confidential,secret,top_secret',
            'is_public_level' => 'required|in:0,1,2',
        ]);

        $level = (int) $validated['is_public_level'];
        unset($validated['is_public_level']);
        $validated['direction'] = $direction;
        $validated['visibility'] = match ($level) {
            2 => Document::VISIBILITY_SYSTEM,
            1 => Document::VISIBILITY_BRANCH,
            default => Document::VISIBILITY_PRIVATE,
        };

        $updatedDocument = $this->documentService->updateDocument($document, $validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật văn bản thành công.',
            'data' => $updatedDocument,
        ]);
    }

    /**
     * Permanently delete a document owned by its type-I clerk uploader.
     */
    public function destroy($id)
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = User::with('branch')->find($userId);
        $document = Document::with('attachments')->find($id);

        if (! $user || ! $document || ! $document->canBeDeletedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ văn thư Chi nhánh loại I đã đăng tải văn bản mới được xóa.',
            ], 403);
        }

        try {
            $this->documentService->deleteDocument($document);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa văn bản và toàn bộ file đính kèm.',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa văn bản. Dữ liệu chưa bị xóa, vui lòng thử lại.',
            ], 500);
        }
    }

    /**
     * Transfer document
     */
    public function transfer(Request $request, $id)
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = User::with('branch')->find($userId);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $document = $this->documentQueryService->getDocumentsForUser($user)->find($id);

        if (! $document) {
            return response()->json(['success' => false, 'message' => 'Not found or forbidden'], 404);
        }

        $validated = $request->validate([
            'target_type' => 'required|in:branch,department',
            'to_branch_ids' => 'required_if:target_type,branch|array|min:1',
            'to_branch_ids.*' => 'integer|distinct',
            'to_department_id' => 'required_if:target_type,department|nullable|integer',
            'note' => 'nullable|string|max:2000',
        ]);

        $note = $validated['note'] ?? null;

        if ($validated['target_type'] === 'branch') {
            if (! $document->canBeTransferredToBranchBy($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ văn thư Chi nhánh loại I đã đăng tải văn bản mới được chuyển đến chi nhánh.',
                ], 403);
            }

            $branchIds = collect($validated['to_branch_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $targetBranches = Branches::query()
                ->where('status', 'active')
                ->where('branch_type', 'type_2')
                ->whereIn('id', $branchIds)
                ->get();

            if ($targetBranches->count() !== $branchIds->count()) {
                return response()->json(['success' => false, 'message' => 'Có chi nhánh nhận không hợp lệ.'], 422);
            }

            $transfer = DB::transaction(function () use ($targetBranches, $document, $user, $note) {
                return $targetBranches->map(fn ($targetBranch) => $this->documentService->transferDocument(
                    $document,
                    $user->branch_id,
                    $targetBranch->id,
                    $user,
                    $note
                ));
            });
        } else {
            if (! $document->canBeDistributedToDepartmentBy($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Văn thư Chi nhánh loại II chỉ được phân phối văn bản đã nhận đến phòng ban của mình.',
                ], 403);
            }

            $department = Department::query()
                ->where('branch_id', $user->branch_id)
                ->where('status', 'active')
                ->find($validated['to_department_id'] ?? 0);

            if (! $department) {
                return response()->json(['success' => false, 'message' => 'Phòng ban nhận không hợp lệ.'], 422);
            }

            $transfer = $this->documentService->transferDocumentToDepartment(
                $document,
                $department->id,
                $user,
                $note
            );
        }

        return response()->json([
            'success' => true,
            'message' => $validated['target_type'] === 'branch'
                ? 'Đã chuyển văn bản đến '.$targetBranches->count().' chi nhánh.'
                : 'Đã phân phối văn bản đến phòng ban.',
            'data' => $transfer,
        ]);
    }
}
