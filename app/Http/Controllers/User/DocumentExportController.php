<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateDocumentLedgerExport;
use App\Models\DocumentExportOperation;
use App\Models\User;
use App\Services\DocumentLedgerExportService;
use App\Support\DocumentExportPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class DocumentExportController extends Controller
{
    public function __invoke(Request $request, ?DocumentLedgerExportService $exportService = null)
    {
        $exportService ??= app(DocumentLedgerExportService::class);
        $userId = Session::get('user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::with(['branch', 'position'])->find($userId);
        abort_unless($user?->isClerk() && $user->branch_id, 403, 'Chỉ Văn thư thuộc chi nhánh mới được xuất sổ văn bản.');

        $validated = $request->validate([
            'direction' => 'required|in:incoming,outgoing',
            'period_type' => 'required|in:month,quarter,year',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required_if:period_type,month|nullable|integer|min:1|max:12',
            'quarter' => 'required_if:period_type,quarter|nullable|integer|min:1|max:4',
            'background' => 'nullable|boolean',
        ]);
        $period = DocumentExportPeriod::from($validated);
        $direction = $validated['direction'];
        // Sổ thuộc chi nhánh, không phải mọi văn bản mà người dùng có quyền xem.
        $documents = $exportService->query((int) $user->branch_id, $validated, $period);

        $lock = Cache::lock(
            'document-ledger-export:user:'.$user->id,
            max(30, (int) config('documents.exports.lock_seconds', 300))
        );

        if (! $lock->get()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Một file sổ văn bản của bạn đang được tạo. Vui lòng chờ hoàn tất rồi thử lại.'], 429)
                : back()->with('error', 'Một file sổ văn bản của bạn đang được tạo. Vui lòng chờ hoàn tất rồi thử lại.');
        }

        try {
            $rowCount = (clone $documents)->reorder()->count('ledger.id');
            $maxRows = max(1000, (int) config('documents.exports.max_rows', 20000));

            if ($rowCount === 0) {
                return $this->errorResponse($request, 'Không có văn bản trong khoảng thời gian đã chọn.', 422);
            }

            if ($rowCount > $maxRows) {
                return $this->errorResponse(
                    $request,
                    "Có {$rowCount} văn bản, vượt giới hạn {$maxRows} dòng cho một lần xuất. Hãy chọn khoảng thời gian ngắn hơn.",
                    422,
                );
            }

            $fileName = $exportService->fileName($validated, $period);
            $backgroundMinRows = max(1, (int) config('documents.exports.background_min_rows', 5000));
            if ($request->boolean('background') && $rowCount >= $backgroundMinRows) {
                return $this->queueExport($request, $user, $validated, $period, $fileName, $rowCount);
            }

            return Excel::download(new \App\Exports\DocumentLedgerWorkbook($documents, $direction, $period->label), $fileName, ExcelWriter::XLSX, [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Document-Export-Rows' => (string) $rowCount,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                $request,
                'Không thể tạo file Excel lúc này. Vui lòng thử lại hoặc chọn khoảng thời gian ngắn hơn.',
                500,
            );
        } finally {
            $lock->release();
        }
    }

    private function queueExport(
        Request $request,
        User $user,
        array $filters,
        DocumentExportPeriod $period,
        string $fileName,
        int $rowCount,
    ): JsonResponse|RedirectResponse {
        $this->cleanupExpiredExports((int) $user->id);

        $active = DocumentExportOperation::query()
            ->where('user_id', $user->id)
            ->where('branch_id', $user->branch_id)
            ->whereIn('status', ['queued', 'running'])
            ->latest('id')
            ->first();
        if ($active) {
            return $this->queuedResponse($request, $active, 'File trước đó vẫn đang được tạo.');
        }

        $disk = (string) config('documents.exports.disk', 'local');
        $operation = DocumentExportOperation::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'direction' => $filters['direction'],
            'period_type' => $filters['period_type'],
            'year' => (int) $filters['year'],
            'month' => isset($filters['month']) ? (int) $filters['month'] : null,
            'quarter' => isset($filters['quarter']) ? (int) $filters['quarter'] : null,
            'period_label' => $period->label,
            'file_name' => $fileName,
            'disk' => $disk,
            'path' => 'document-exports/'.$user->id.'/'.Str::uuid().'.xlsx',
            'status' => 'queued',
            'row_count' => $rowCount,
            'expires_at' => now()->addHours(max(1, (int) config('documents.exports.ttl_hours', 24))),
        ]);

        try {
            GenerateDocumentLedgerExport::dispatch($operation->id);
        } catch (\Throwable $exception) {
            $operation->update([
                'status' => 'failed',
                'error_message' => 'Không thể đưa yêu cầu xuất Excel vào hàng đợi.',
                'finished_at' => now(),
            ]);

            throw $exception;
        }

        return $this->queuedResponse($request, $operation, 'Đã xếp file vào hàng đợi. Trang sẽ tự tải khi tạo xong.');
    }

    private function queuedResponse(Request $request, DocumentExportOperation $operation, string $message): JsonResponse|RedirectResponse
    {
        $payload = [
            'message' => $message,
            'export' => [
                'id' => $operation->id,
                'status' => $operation->status,
                'status_url' => route('documents_export_status', $operation),
            ],
        ];

        return $request->expectsJson()
            ? response()->json($payload, 202)
            : back()->with('success', $message)->with('document_export_status_url', $payload['export']['status_url']);
    }

    private function errorResponse(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], $status)
            : back()->with('error', $message);
    }

    private function cleanupExpiredExports(int $userId): void
    {
        DocumentExportOperation::query()
            ->where('user_id', $userId)
            ->where('expires_at', '<=', now())
            ->get()
            ->each(function (DocumentExportOperation $operation): void {
                Storage::disk($operation->disk)->delete($operation->path);
                $operation->delete();
            });
    }
}
