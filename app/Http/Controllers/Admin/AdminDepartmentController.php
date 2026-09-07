<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Branches;

class AdminDepartmentController extends Controller
{
    public function index()
    {
        $list_department = Department::with('branch')->orderBy('id', 'asc')->get();
        $list_branch = Branches::select('id', 'branch_name')->get();
        return view('admin.departments.list_department', compact('list_department', 'list_branch'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id'
        ]);

        $department = Department::create([
            'department_name' => $request->department_name,
            'branch_id' => $request->branch_id,
            'status' => 'active'
        ]);

        $branch_name = Branches::where('id', $request->branch_id)->value('branch_name');

        return response()->json([
            'success' => 'Thêm phòng ban thành công!',
            'status' => true,
            'department' => $department,
            'branch_name' => $branch_name
        ]);
    }

    public function edit(Request $request)
    {
        $department = Department::find($request->department_id);
        return response()->json([
            'department' => $department
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'department_name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id'
        ]);

        $department = Department::find($request->department_id);
        $department->department_name = $request->department_name;
        $department->branch_id = $request->branch_id;
        $department->save();

        $branch_name = Branches::where('id', $request->branch_id)->value('branch_name');

        return response()->json([
            'message' => 'Cập nhật phòng ban thành công!',
            'status' => true,
            'department' => $department,
            'branch_name' => $branch_name
        ]);
    }
}
