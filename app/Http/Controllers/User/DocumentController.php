<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Branches;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentReportService;
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

    public function document_reports(Request $request, DocumentReportService $reports)
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

        // Báo cáo theo đúng Ngày văn bản; service dùng khoảng đầu năm đến
        // đầu năm sau để index issued_date vẫn được sử dụng.
        return view('user.page.document_report', $reports->forUser($user, $selectedYear));
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
