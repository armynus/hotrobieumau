<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branches;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminDepartmentController extends Controller
{
    public function index()
    {
        $list_department = Department::with(['branch:id,branch_name', 'parent:id,department_name'])->orderBy('branch_id')->orderBy('department_name')->get();
        $list_branch = Branches::select('id', 'branch_name', 'branch_type')->orderBy('branch_name')->get();

        return view('admin.departments.list_department', compact('list_department', 'list_branch'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateDepartment($request);
        $validated['department_code'] = $validated['department_code'] ?? null;
        $validated['parent_id'] = $validated['parent_id'] ?? null;
        $department = Department::create($validated);

        $branch_name = Branches::where('id', $request->branch_id)->value('branch_name');

        return response()->json([
            'success' => 'Thêm phòng ban thành công!',
            'status' => true,
            'department' => $department,
            'branch_name' => $branch_name,
        ]);
    }

    public function edit(Request $request)
    {
        $department = Department::findOrFail($request->integer('department_id'));

        return response()->json([
            'department' => $department,
        ]);
    }

    public function update(Request $request)
    {
        $department = Department::findOrFail($request->integer('department_id'));
        $validated = $this->validateDepartment($request, $department);
        $validated['department_code'] = $validated['department_code'] ?? null;
        $validated['parent_id'] = $validated['parent_id'] ?? null;
        $department->update($validated);

        $branch_name = Branches::where('id', $request->branch_id)->value('branch_name');

        return response()->json([
            'message' => 'Cập nhật phòng ban thành công!',
            'status' => true,
            'department' => $department,
            'branch_name' => $branch_name,
        ]);
    }

    private function validateDepartment(Request $request, ?Department $department = null): array
    {
        $branchId = $request->integer('branch_id');

        return $request->validate([
            'department_name' => [
                'required', 'string', 'max:255',
                Rule::unique('departments', 'department_name')->where(fn ($query) => $query->where('branch_id', $branchId))->ignore($department?->id),
            ],
            'department_code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('departments', 'department_code')->where(fn ($query) => $query->where('branch_id', $branchId))->ignore($department?->id),
            ],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'parent_id' => [
                'nullable', 'integer', Rule::notIn(array_filter([$department?->id])),
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('branch_id', $branchId)),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'department_name.unique' => 'Tên phòng ban đã tồn tại trong chi nhánh này.',
            'department_code.unique' => 'Mã phòng ban đã tồn tại trong chi nhánh này.',
            'parent_id.exists' => 'Phòng ban cấp trên phải thuộc cùng chi nhánh.',
        ]);
    }
}
