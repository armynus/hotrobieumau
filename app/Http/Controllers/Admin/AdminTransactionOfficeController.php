<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branches;
use App\Models\TransactionOffice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTransactionOfficeController extends Controller
{
    public function index()
    {
        $transactionOffices = TransactionOffice::query()
            ->with('branch:id,branch_name,branch_code')
            ->orderBy('branch_id')
            ->orderBy('office_name')
            ->get();
        $branches = Branches::query()
            ->select('id', 'branch_name', 'branch_code', 'branch_type')
            ->where('status', 'active')
            ->orderBy('branch_name')
            ->get();

        return view('admin.transaction_offices.index', compact('transactionOffices', 'branches'));
    }

    public function store(Request $request)
    {
        $office = TransactionOffice::create($this->validateOffice($request));

        return response()->json([
            'status' => true,
            'message' => 'Đã thêm phòng giao dịch.',
            'office' => $office,
        ]);
    }

    public function edit(Request $request)
    {
        $office = TransactionOffice::findOrFail($request->integer('transaction_office_id'));

        return response()->json([
            'office' => $office,
            'has_assigned_users' => $office->users()->exists(),
        ]);
    }

    public function update(Request $request)
    {
        $office = TransactionOffice::findOrFail($request->integer('transaction_office_id'));
        $office->update($this->validateOffice($request, $office));

        return response()->json([
            'status' => true,
            'message' => 'Đã cập nhật phòng giao dịch.',
            'office' => $office,
        ]);
    }

    private function validateOffice(Request $request, ?TransactionOffice $office = null): array
    {
        $branchId = $request->integer('branch_id');

        $validated = $request->validate([
            'branch_id' => [
                'required', 'integer', Rule::exists('branches', 'id'),
                ...($office?->users()->exists() ? [Rule::in([$office->branch_id])] : []),
            ],
            'office_name' => [
                'required', 'string', 'max:255',
                Rule::unique('transaction_offices', 'office_name')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($office?->id),
            ],
            'office_code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('transaction_offices', 'office_code')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($office?->id),
            ],
            'office_address' => ['nullable', 'string', 'max:255'],
            'office_place' => ['nullable', 'string', 'max:255'],
            'office_phone' => ['nullable', 'string', 'max:30'],
            'office_fax' => ['nullable', 'string', 'max:30'],
            'office_email' => ['nullable', 'email', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'branch_id.required' => 'Vui lòng chọn chi nhánh quản lý.',
            'branch_id.in' => 'PGD đã có nhân viên. Hãy điều chuyển nhân viên trước khi đổi chi nhánh quản lý.',
            'office_name.required' => 'Vui lòng nhập tên phòng giao dịch.',
            'office_name.unique' => 'Tên phòng giao dịch đã tồn tại trong chi nhánh này.',
            'office_code.unique' => 'Mã phòng giao dịch đã tồn tại trong chi nhánh này.',
            'office_email.email' => 'Email phòng giao dịch chưa đúng định dạng.',
        ]);

        foreach (['office_code', 'office_address', 'office_place', 'office_phone', 'office_fax', 'office_email', 'manager_name'] as $field) {
            $validated[$field] = $validated[$field] ?? null;
        }

        return $validated;
    }
}
