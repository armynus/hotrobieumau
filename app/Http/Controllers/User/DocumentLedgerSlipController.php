<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DocumentLedgerEntry;
use App\Models\User;
use App\Services\DocumentLedgerSlipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class DocumentLedgerSlipController extends Controller
{
    public function __invoke(Request $request, int $entry, DocumentLedgerSlipService $slips)
    {
        $clerk = User::with('branch')->find(Session::get('user_id'));
        abort_unless($clerk?->isClerk() && $clerk->branch_id, 403, 'Chỉ văn thư cùng chi nhánh được tải phiếu trình.');
        // Dùng ID dòng sổ, không dùng số sổ vì được phép trùng số.
        $entry = DocumentLedgerEntry::where('branch_id', $clerk->branch_id)->findOrFail($entry);
        $options = $request->validate([
            'print_date' => 'required|date_format:Y-m-d',
            'submitted_to' => 'required|string|max:255',
            'department_name' => 'nullable|string|max:255', 'place_name' => 'nullable|string|max:100',
            'signature_title' => 'nullable|string|max:255', 'prepared_by' => 'nullable|string|max:255',
        ], ['print_date.required' => 'Vui lòng chọn ngày lập phiếu.', 'submitted_to.required' => 'Vui lòng nhập nơi kính trình.']);
        $lock = Cache::lock('document-ledger-slip:user:'.$clerk->id, 60);
        if (! $lock->get()) {
            return response()->json(['message' => 'Một phiếu trình đang được tạo. Vui lòng chờ rồi thử lại.'], 429);
        }
        try {
            $path = $slips->createFile($clerk, $entry, $options);

            return response()->download($path, 'Phieu-trinh_'.$entry->book.'_'.$entry->year.'_dong-'.$entry->id.'.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Không tạo được phiếu trình. Hãy kiểm tra file mẫu hoặc thử lại sau.'], 500);
        } finally {
            $lock->release();
        }
    }
}
