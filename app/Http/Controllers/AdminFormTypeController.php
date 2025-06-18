<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormType;
use App\Models\SupFormType;
use Yajra\DataTables\Facades\DataTables;

class AdminFormTypeController extends Controller
{
    public function update (Request $request)
    {
        // Validate the request data
        $request->validate([
            'id' => 'required|string|max:255',
            'type_name' => 'required|string|max:255',
        ]);
        // Find the FormType instance by ID
        $formType = FormType::find($request->id);
        if($formType){
            // Kiểm tra xem đã tồn tại type_name giống vậy chưa (trừ chính nó)
            $isDuplicate = FormType::where('type_name', $request->type_name)
                ->where('id', '!=', $request->id)
                ->exists();

            if ($isDuplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trùng tên thể loại đã tồn tại',
                ]);
            }

            // Cập nhật nếu không trùng
            $formType->type_name = $request->type_name;
            $formType->save();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thể loại thành công',
                'data' => $formType,
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Lỗi dữ liệu không tồn tại',
            ]);
        }
    }
    public function edit(Request $request)
    {
        // Validate the request data
        $request->validate([
            'id' => 'required|string|max:255',
        ]);
        $formType = FormType::find($request->id);
        if($formType){
            return response()->json([
                'success' => true,
                'data' => $formType,
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Lỗi dữ liệu không tồn tại',
            ]);
        }

    }
    public function getDataFormType(Request $request)
    {
        try {
            $formtype = FormType::select(['id', 'type_name', 'created_at', 'updated_at']);

            return DataTables::of($formtype)->make(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    function index()
    {
        $data =  FormType::orderBy('id', 'asc')->paginate(10); // Hiển thị 10 tài khoản mỗi trang;
        $fields = [
            'type_name' => 'Tên thể loại',
        ];
        $edit_fields = [
            'edit_type_name' => 'Tên thể loại',
        ];
        return view('admin.form_type.list_form_type', compact('data','fields', 'edit_fields'));
    }
    public function create(Request $request)
    {
        //Check dữ liệu đã tồn tại chưa
        $check = FormType::where('type_name', $request->input('type_name'))->first();
        if ($check) {
            return response()->json([
                'success' => false,
                'message' => 'Thể loại đã tồn tại',
            ]);
        }
        // Validate the request data
        $request->validate([
            'type_name' => 'required|string|max:255',
        ]);
        // Create a new FormType instance and save it to the database
        $formType = new FormType();
        $formType->type_name = $request->input('type_name');
        $formType->save();

        return response ()->json([
            'success' => true,
            'message' => 'Thêm thể loại thành công',
            'data' => $formType,
        ]);
    }
}
