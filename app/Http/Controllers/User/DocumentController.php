<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Branches;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DocumentController extends Controller
{
    public function documents_forward()
    {
        return $this->documentList();
    }

    public function incomingDocuments()
    {
        return redirect()->route('documents_forward');
    }

    public function outgoingDocuments()
    {
        return redirect()->route('documents_forward');
    }

    private function documentList()
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return redirect()->route('login_user');
        }

        $user = \App\Models\User::with('branch')->find($userId);
        if (! $user) {
            return redirect()->route('login_user');
        }

        $documentTypes = DocumentType::query()->where('status', 'active')->orderBy('name')->get();
        $type2Branches = Branches::query()
            ->where('branch_type', 'type_2')
            ->where('status', 'active')
            ->orderBy('branch_name')
            ->get();
        $departments = Department::query()
            ->where('branch_id', $user->branch_id)
            ->where('status', 'active')
            ->orderBy('department_name')
            ->get();

        return view('user.page.document_list', compact('documentTypes', 'type2Branches', 'departments'))
            ->with('directors', app(\App\Services\DocumentRecipientService::class)->directors((int) $user->branch_id));
    }

    public function document_register()
    {
        return $this->documentRegister(Document::DIRECTION_INCOMING);
    }

    public function registerIncoming()
    {
        return $this->documentRegister(Document::DIRECTION_INCOMING);
    }

    public function registerOutgoing()
    {
        return $this->documentRegister(Document::DIRECTION_OUTGOING);
    }

    public function registerDecision()
    {
        return $this->documentRegister(Document::DIRECTION_DECISION);
    }

    private function documentRegister(string $direction)
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return redirect()->route('login_user');
        }

        $user = \App\Models\User::find($userId);
        if (! $user || ! $user->canUploadDocument()) {
            return redirect()->route('documents_forward')->with('error', 'Chỉ Văn thư Chi nhánh Loại 1 mới có quyền đăng tải văn bản.');
        }

        $documentTypes = DocumentType::query()->where('status', 'active')->get();
        $type2Branches = Branches::query()->where('branch_type', 'type_2')->where('status', 'active')->get();

        $departments = Department::where('branch_id', $user->branch_id)->where('status', 'active')->orderBy('department_name')->get();
        return view('user.page.document_register', compact('documentTypes', 'type2Branches', 'direction', 'departments'))
            ->with('directors', app(\App\Services\DocumentRecipientService::class)->directors((int) $user->branch_id));
    }

    public function document_reports(Request $request, DocumentQueryService $documentQueryService)
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return redirect()->route('login_user');
        }

        $user = \App\Models\User::with(['branch', 'position'])->findOrFail($userId);
        $selectedYear = (int) $request->query('year', now()->year);

        if ($selectedYear < 2000 || $selectedYear > 2100) {
            $selectedYear = (int) now()->year;
        }

        $documentsQuery = Document::query();

        // Báo cáo theo đúng "Ngày văn bản" (issued_date), không lấy ngày đến,
        // ngày chuyển hay ngày tải lên làm ngày thay thế vì sẽ làm sai niên độ.
        $years = (clone $documentsQuery)
            ->whereNotNull('issued_date')
            ->selectRaw('YEAR(issued_date) as report_year')
            ->distinct()
            ->orderByDesc('report_year')
            ->pluck('report_year')
            ->map(fn ($year) => (int) $year)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        if (! $years->contains($selectedYear)) {
            $years = $years->push($selectedYear)->unique()->sortDesc()->values();
        }

        $monthlyIncomingCounts = array_fill(0, 12, 0);
        $monthlyOutgoingCounts = array_fill(0, 12, 0);
        $monthlyDecisionCounts = array_fill(0, 12, 0);
        $monthlyUnclassifiedCounts = array_fill(0, 12, 0);
        $monthlyRows = (clone $documentsQuery)
            ->whereYear('issued_date', $selectedYear)
            ->selectRaw('MONTH(issued_date) as report_month, direction, COUNT(*) as total')
            ->groupByRaw('MONTH(issued_date), direction')
            ->get();

        foreach ($monthlyRows as $row) {
            $monthIndex = (int) $row->report_month - 1;
            if ($monthIndex < 0 || $monthIndex > 11) {
                continue;
            }

            if ($row->direction === Document::DIRECTION_DECISION) {
                $monthlyDecisionCounts[$monthIndex] = (int) $row->total;
            } elseif ($row->direction === Document::DIRECTION_OUTGOING) {
                $monthlyOutgoingCounts[$monthIndex] = (int) $row->total;
            } elseif ($row->direction === Document::DIRECTION_INCOMING) {
                $monthlyIncomingCounts[$monthIndex] = (int) $row->total;
            } else {
                $monthlyUnclassifiedCounts[$monthIndex] += (int) $row->total;
            }
        }
        $monthlyCounts = array_map(
            fn ($incoming, $outgoing, $decision, $unclassified) => $incoming + $outgoing + $decision + $unclassified,
            $monthlyIncomingCounts,
            $monthlyOutgoingCounts,
            $monthlyDecisionCounts,
            $monthlyUnclassifiedCounts
        );

        $visibleQuery = $documentQueryService->getDocumentsForUser($user);
        $visibleTotal = (clone $visibleQuery)->count();
        $readStatusQuery = $documentQueryService->getDocumentsForUser($user, [
            'exclude_archive_imports' => true,
        ]);
        $readStatusTotal = (clone $readStatusQuery)->count();
        $readTotal = (clone $readStatusQuery)
            ->whereHas('reads', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        return view('user.page.document_report', [
            'selectedYear' => $selectedYear,
            'years' => $years,
            'monthlyCounts' => $monthlyCounts,
            'monthlyIncomingCounts' => $monthlyIncomingCounts,
            'monthlyOutgoingCounts' => $monthlyOutgoingCounts,
            'monthlyDecisionCounts' => $monthlyDecisionCounts,
            'monthlyUnclassifiedCounts' => $monthlyUnclassifiedCounts,
            'totalSystem' => (clone $documentsQuery)->count(),
            'incomingTotal' => (clone $documentsQuery)->where('direction', Document::DIRECTION_INCOMING)->count(),
            'outgoingTotal' => (clone $documentsQuery)->where('direction', Document::DIRECTION_OUTGOING)->count(),
            'decisionTotal' => (clone $documentsQuery)->where('direction', Document::DIRECTION_DECISION)->count(),
            'unclassifiedTotal' => (clone $documentsQuery)->where('direction', Document::DIRECTION_UNCLASSIFIED)->count(),
            'visibleTotal' => $visibleTotal,
            'readTotal' => $readTotal,
            'unreadTotal' => max(0, $readStatusTotal - $readTotal),
            'yearTotal' => array_sum($monthlyCounts),
            'missingIssuedDateTotal' => (clone $documentsQuery)->whereNull('issued_date')->count(),
        ]);
    }

    public function document_detail($id)
    {
        $userId = Session::get('user_id');
        if (! $userId) {
            return redirect()->route('login_user');
        }

        $user = \App\Models\User::with('branch')->find($userId);
        if (! $user) {
            return redirect()->route('login_user');
        }

        $type2Branches = Branches::query()
            ->where('branch_type', 'type_2')
            ->where('status', 'active')
            ->orderBy('branch_name')
            ->get();
        $departments = Department::query()
            ->where('branch_id', $user->branch_id)
            ->where('status', 'active')
            ->orderBy('department_name')
            ->get();
        $documentTypes = DocumentType::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('user.page.document_detail', compact('id', 'type2Branches', 'departments', 'documentTypes'))
            ->with('directors', app(\App\Services\DocumentRecipientService::class)->directors((int) $user->branch_id));
    }
}
