<?php

namespace App\Http\Controllers\User;

use App\Exports\DocumentLedgerWorkbook;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\User;
use App\Support\DocumentExportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class DocumentExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $userId = Session::get('user_id');
        if (!$userId) return redirect()->route('login');

        $user = User::with(['branch', 'position'])->find($userId);
        abort_unless($user?->isClerk() && $user->branch_id, 403, 'Chỉ Văn thư thuộc chi nhánh mới được xuất sổ văn bản.');

        $validated = $request->validate([
            'direction' => 'required|in:incoming,outgoing',
            'period_type' => 'required|in:month,quarter,year',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required_if:period_type,month|nullable|integer|min:1|max:12',
            'quarter' => 'required_if:period_type,quarter|nullable|integer|min:1|max:4',
        ]);
        $period = DocumentExportPeriod::from($validated);
        $direction = $validated['direction'];
        // Sổ thuộc chi nhánh, không phải mọi văn bản mà người dùng có quyền xem.
        $documents = Document::query()
            ->join('document_ledger_entries as ledger', 'ledger.document_id', '=', 'documents.id')
            ->where('ledger.branch_id', $user->branch_id)
            ->where('ledger.year', $validated['year'])
            ->whereIn('ledger.book', $direction === Document::DIRECTION_INCOMING ? ['incoming'] : ['outgoing', 'decision'])
            ->when($validated['period_type'] !== 'year', fn ($query) => $query->whereBetween('ledger.registered_date', [
                $period->start->toDateString(), $period->end->toDateString(),
            ]))
            ->orderBy('ledger.sequence_number')
            ->orderBy('ledger.number_key')
            ->orderBy('ledger.id');
        $documents->select([
            'documents.id',
            'documents.direction',
            'documents.registry_number',
            'documents.document_code',
            'documents.title',
            'documents.issued_date',
            'documents.received_date',
            'documents.forwarded_date',
            'documents.issuing_agency',
            'documents.signer',
            'documents.recipient',
            'documents.archive_recipient',
            'documents.copy_count',
            'documents.receipt_signature',
            'documents.notes',
            'documents.created_at',
            'ledger.number as entry_number',
            'ledger.document_code as entry_code',
            'ledger.registered_date as entry_date',
            'ledger.book as entry_book',
        ]);

        $lock = Cache::lock(
            'document-ledger-export:user:' . $user->id,
            max(30, (int) config('documents.exports.lock_seconds', 300))
        );

        if (!$lock->get()) {
            return back()->with('error', 'Một file sổ văn bản của bạn đang được tạo. Vui lòng chờ hoàn tất rồi thử lại.');
        }

        try {
            $rowCount = (clone $documents)->reorder()->count('documents.id');
            $maxRows = max(1000, (int) config('documents.exports.max_rows', 20000));

            if ($rowCount === 0) {
                return back()->with('error', 'Không có văn bản trong khoảng thời gian đã chọn.');
            }

            if ($rowCount > $maxRows) {
                return back()->with('error', "Có {$rowCount} văn bản, vượt giới hạn {$maxRows} dòng cho một lần xuất. Hãy chọn khoảng thời gian ngắn hơn.");
            }

            $typeName = $direction === Document::DIRECTION_OUTGOING ? 'di' : 'den';
            $fileName = "So-van-ban-{$typeName}_{$period->fileSuffix}.xlsx";
            $export = new DocumentLedgerWorkbook($documents, $direction, $period->label);

            return Excel::download($export, $fileName, ExcelWriter::XLSX, [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Không thể tạo file Excel lúc này. Vui lòng thử lại hoặc chọn khoảng thời gian ngắn hơn.');
        } finally {
            $lock->release();
        }
    }
}
