<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupFormType;
use Yajra\DataTables\Facades\DataTables;

class AdminSupFormTypeController extends Controller
{
    public function delete(Request $request)
    {
        // Validate the request data
        $request->validate([
            'id' => 'required|integer',
        ]);

        // Find the SupFormType instance by ID
        $supFormType = SupFormType::find($request->id);
        if ($supFormType) {
            $supFormType->delete();
            return response()->json([
                'success' => true,
                'message' => 'Xóa thể loại phụ thành công',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi dữ liệu không tồn tại',
            ]);
        }
    }
    public function create(Request $request)
    {
        //Check dữ liệu đã tồn tại chưa
        $check = SupFormType::where('name', $request->name)
            ->first();
        if ($check) {
            return response()->json([
                'success' => false,
                'message' => 'Tên thể loại đã tồn tại',
            ]);
        }
        // Validate the request data
        $request->validate([
            'form_type_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        // Create a new SupFormType instance
        $supFormType = new SupFormType();
        $supFormType->form_type_id = $request->form_type_id;
        $supFormType->name = $request->name;
        $supFormType->description = $request->description;
        $supFormType->save();

        // Lấy lại record vừa tạo kèm tên loại chính
        $supFormType = SupFormType::join('form_type', 'sup_form_type.form_type_id', '=', 'form_type.id')
            ->where('sup_form_type.id', $supFormType->id)
            ->select([
                'sup_form_type.id',
                'sup_form_type.name',
                'form_type.type_name as form_type_name',
                'sup_form_type.description',
                'sup_form_type.updated_at',
            ])
            ->first();
        return response()->json([
            'success' => true,
            'message' => 'Thêm thể loại phụ thành công',
            'data' => $supFormType,
        ]);
    }
    public function edit(Request $request)
    {
        // Validate the request data
        $request->validate([
            'id' => 'required|integer',
        ]);

        // Find the SupFormType instance by ID
        $supFormType = SupFormType::find($request->id);
        if ($supFormType) {
            return response()->json([
                'success' => true,
                'data' => $supFormType,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi dữ liệu không tồn tại',
            ]);
        }
    }
    public function update(Request $request)
    {
        // Validate the request data
        $request->validate([
            'id' => 'required|integer',
            'form_type_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);
        $check = SupFormType::where('name', $request->name)
            ->where('id', '!=', $request->id)  
            ->where('form_type_id', $request->form_type_id)
            ->first();
        if ($check) {
            return response()->json([
                'success' => false,
                'message' => 'Tên thể loại đã tồn tại',
            ]);
        }

        // Find the SupFormType instance by ID
        $supFormType = SupFormType::find($request->id);
        if ($supFormType) {
            // Update the fields
            $supFormType->form_type_id = $request->form_type_id;
            $supFormType->name = $request->name;
            $supFormType->description = $request->description;
            $supFormType->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thể loại phụ thành công',
                'data' => $supFormType,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi dữ liệu không tồn tại',
            ]);
        }
    }
    function index()
    {
        $fields = [
            'form_type_id' => 'Thuộc thể loại',
            'name' => 'Tên thể loại phụ',
            'description' => 'Mô tả (nếu có)',
        ];
        $edit_fields = [
            'edit_form_type_id' => 'Thuộc thể loại',
            'edit_name' => 'Tên thể loại phụ',
            'edit_description' => 'Mô tả (nếu có)',
        ];
        return view('admin.form_type.list_sup_form_type', compact('fields', 'edit_fields'));
    }
    public function getDataSupFormType(Request $request)
    {
        try {
            // Lấy sup_form_type kèm relationship formType
            $supformtype = SupFormType::with('FormType')
                ->select(['id', 'name', 'form_type_id', 'description', 'created_at', 'updated_at']);

            return DataTables::of($supformtype)
                // Thêm cột form_type_name từ relationship
                ->addIndexColumn()
                ->addColumn('form_type_name', function (SupFormType $row) {
                    return optional($row->FormType)->type_name;
                })
                ->make(true);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
