<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentLedgerEntry;
use App\Models\User;
use App\Services\DocumentLedgerFormService;
use App\Services\DocumentLedgerImportService;
use App\Services\DocumentLedgerReader;
use App\Services\DocumentLedgerService;
use App\Services\DocumentQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class DocumentLedgerController extends Controller
{
    private const BOOK_LABELS = ['incoming' => 'Sổ văn bản đến', 'outgoing' => 'Sổ văn bản đi', 'decision' => 'Sổ quyết định'];

    public function index(Request $request, DocumentLedgerService $ledgerService)
    {
        $clerk = $this->clerk();
        $filters = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'book' => 'nullable|in:incoming,outgoing,decision',
            'q' => 'nullable|string|max:255',
            'check' => 'nullable|boolean',
        ]);
        $year = (int) ($filters['year'] ?? now()->year);
        $book = $filters['book'] ?? 'incoming';
        $keyword = trim($filters['q'] ?? '');
        $checkOnly = $request->boolean('check');
        if ($request->has('draw')) {
            return response()->json(app(\App\Services\DocumentLedgerTableService::class)->data($clerk, $request, $year, $book, $keyword));
        }
        $base = DocumentLedgerEntry::query()->where('branch_id', $clerk->branch_id)->where('year', $year);
        $counts = (clone $base)->selectRaw('book, count(*) as aggregate')->groupBy('book')->pluck('aggregate', 'book');
        $nextNumber = $ledgerService->nextNumber((int) $clerk->branch_id, $year, $book);
        $bookLabels = self::BOOK_LABELS;
        $clerk->loadMissing(['department', 'branch']);
        $signers = User::query()
            ->where('branch_id', $clerk->branch_id)
            ->where('status', 'active')
            ->whereHas('position', fn ($q) => $q->whereIn('level', [1, 2]))
            ->with('position')
            ->get()
            ->sortBy(fn($user) => $user->position->level ?? 999);

        return view('user.page.document_ledger', compact('clerk', 'year', 'book', 'keyword', 'counts', 'nextNumber', 'bookLabels', 'checkOnly', 'signers'));
    }

    public function nextNumber(Request $request, DocumentLedgerService $ledgerService)
    {
        $clerk = $this->clerk();
        $data = $request->validate(['year' => 'required|integer|min:2000|max:2100', 'book' => 'required|in:incoming,outgoing,decision']);

        return response()->json(['number' => $ledgerService->nextNumber((int) $clerk->branch_id, (int) $data['year'], $data['book'])]);
    }

    public function uploadLookup(Request $request, \App\Services\DocumentLedgerUploadService $lookup)
    {
        $user = $this->clerk();
        $data = $request->validate([
            'book' => 'required|in:incoming,outgoing,decision',
            'year' => 'required|integer|min:2000|max:2100',
            'q' => 'required|string|max:255',
        ]);

        return response()->json($lookup->lookup($user, $data['book'], (int) $data['year'], $data['q']));
    }

    public function candidates(Request $request, DocumentQueryService $queryService)
    {
        $clerk = $this->clerk();
        $data = $request->validate(['q' => 'required|string|min:1|max:255']);
        $documents = $queryService->getDocumentsForUser($clerk, ['keyword' => trim($data['q'])])
            // Đã có dòng sổ tại chi nhánh (bất kể năm/loại) thì dùng bút chì để sửa.
            ->whereDoesntHave('ledgerEntries', fn ($query) => $query->where('branch_id', $clerk->branch_id))
            ->where(fn ($query) => $query->where('managing_branch_id', $clerk->branch_id)
                ->orWhereHas('transfers', fn ($transfer) => $transfer->where('to_branch_id', $clerk->branch_id)))
            ->withCount('attachments')
            ->limit(20)->get();

        return response()->json($documents->map(fn ($document) => [
            'id' => $document->id,
            'document_code' => $document->document_code,
            'title' => $document->title,
            'direction' => $document->direction,
            'registered_date' => ($document->direction === Document::DIRECTION_INCOMING ? $document->received_date : $document->forwarded_date)?->format('Y-m-d'),
            'own_branch' => (int) $document->managing_branch_id === (int) $clerk->branch_id,
            'form_data' => app(DocumentLedgerFormService::class)->formData($document, $clerk),
        ]));
    }

    public function register(Request $request, int $document, DocumentQueryService $queryService)
    {
        $clerk = $this->clerk();
        $request->validate(['operation' => 'required|in:register']);
        $document = $queryService->getDocumentsForUser($clerk)->findOrFail($document);
        $entry = app(DocumentLedgerFormService::class)->save($clerk, $document, $request->all());

        return response()->json(['message' => 'Đã lưu thông tin vào sổ văn bản.', 'entry' => $entry]);
    }

    public function create(Request $request, DocumentLedgerFormService $formService)
    {
        $entry = $formService->save($this->clerk(), null, $request->all());

        return response()->json(['message' => 'Đã ghi sổ văn bản. Không tạo văn bản trong kho.', 'entry' => $entry], 201);
    }

    public function update(Request $request, int $entry, DocumentLedgerFormService $formService)
    {
        $clerk = $this->clerk();
        $entry = DocumentLedgerEntry::where('branch_id', $clerk->branch_id)->findOrFail($entry);
        $entry = $formService->save($clerk, null, $request->all(), $entry);

        return response()->json(['message' => 'Đã cập nhật sổ. Thông tin kho văn bản không thay đổi.', 'entry' => $entry]);
    }

    public function import(Request $request, DocumentLedgerReader $reader, DocumentLedgerImportService $importer)
    {
        $clerk = $this->clerk();
        $data = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200',
            'year' => 'required|integer|min:2000|max:2100',
            'direction' => 'required|in:incoming,outgoing',
            'sheets' => 'required|string|max:2000',
            'preview' => 'required|boolean',
            'overwrite' => 'nullable|boolean',
            'preview_token' => 'nullable|string|size:64',
        ], [
            'file.required' => 'Vui lòng chọn file Excel cần nhập.',
            'file.mimes' => 'Sổ văn bản phải là file .xlsx hoặc .xls.',
            'file.max' => 'File Excel tối đa 50 MB.',
            'sheets.required' => 'Vui lòng nhập tên sheet cần lấy dữ liệu, mỗi tên một dòng.',
        ]);
        $lock = Cache::lock('document-ledger-web:branch:'.$clerk->branch_id, 3600);
        if (! $lock->get()) {
            return response()->json(['message' => 'Chi nhánh đang xử lý một sổ Excel. Vui lòng chờ rồi thử lại.'], 429);
        }

        try {
            set_time_limit(max(30, min(300, (int) config('documents.ledger.web_import_timeout', 180))));
            $file = $request->file('file');
            $preview = $request->boolean('preview');
            $overwrite = $request->boolean('overwrite');
            $sheets = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['sheets']))));
            $fingerprint = hash('sha256', hash_file('sha256', $file->getRealPath()).json_encode([
                $clerk->id, $clerk->branch_id, $data['direction'], (int) $data['year'], $sheets, $overwrite,
            ]));
            $sessionKey = 'document_ledger_preview';
            $confirmation = $request->session()->get($sessionKey);
            if (! $preview && (! is_array($confirmation)
                || ! hash_equals($confirmation['fingerprint'] ?? '', $fingerprint)
                || ! hash_equals($confirmation['token'] ?? '', $data['preview_token'] ?? '')
                || ($confirmation['expires_at'] ?? 0) < time())) {
                throw ValidationException::withMessages(['file' => 'File hoặc lựa chọn đã thay đổi. Hãy kiểm tra trước khi xác nhận nhập.']);
            }
            $maxRows = max(100, (int) config('documents.ledger.web_import_max_rows', 5000));
            $rows = $reader->read($file->getRealPath(), $data['direction'], $sheets, $maxRows);
            if (count($rows) > $maxRows) {
                throw ValidationException::withMessages(['file' => "Sổ có hơn {$maxRows} dòng. Hãy tách file hoặc dùng lệnh import trên máy chủ."]);
            }
            $stats = $importer->import($rows, $clerk, (int) $data['year'], $file->getClientOriginalName(), $preview, $overwrite);
            $result = ['message' => $preview ? 'Đã kiểm tra sổ, chưa lưu dữ liệu.' : 'Đã nhập dữ liệu sổ văn bản.', 'stats' => $stats];
            if ($preview) {
                $token = bin2hex(random_bytes(32));
                $request->session()->put($sessionKey, ['token' => $token, 'fingerprint' => $fingerprint, 'expires_at' => time() + 1800]);
                $result['preview_token'] = $token;
                $result['sample'] = $stats['sample'];
            } else {
                $request->session()->forget($sessionKey);
            }

            return response()->json($result);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } finally {
            $lock->release();
        }
    }

    private function clerk(): User
    {
        $clerk = User::with('branch')->find(Session::get('user_id'));
        abort_unless($clerk?->isClerk() && $clerk->branch_id, 403, 'Chỉ Văn thư thuộc chi nhánh mới được quản lý sổ văn bản.');

        return $clerk;
    }
}
