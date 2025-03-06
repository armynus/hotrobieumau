<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\FormField;
use App\Models\SupportForm;

class AdminFormFieldController extends Controller
{
    public function add_form_field(Request $request){
        $request->validate([
            'field_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('form_fields')->where(function ($query) use ($request) {
                    return $query->where('field_name', $request->field_name);
                }),
            ],
            'field_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('form_fields')->where(function ($query) use ($request) {
                    return $query->where('field_code', $request->field_code);
                }),
            ],
            'placeholder' => 'nullable|string|max:255',
            'data_type' => 'required|string|max:50',
        ], [
            'field_name.required' => 'Vui lòng nhập tên trường dữ liệu.',
            'field_name.unique' => 'Tên trường dữ liệu đã tồn tại, vui lòng chọn tên khác.',
            'field_code.required' => 'Vui lòng nhập mã dữ liệu.',
            'field_code.unique' => 'Mã dữ liệu đã tồn tại, vui lòng chọn mã khác.',
            'data_type.required' => 'Vui lòng chọn kiểu dữ liệu.',
        ]);

        $formField = FormField::create($request->only(['field_name', 'field_code', 'placeholder', 'data_type']));

        return response()->json([
            'status' => true,
            'form_field' => $formField,
            'message' => 'Trường dữ liệu đã được thêm thành công!'
        ]);
    }
    
    public function admin_edit_field(Request $request ){
        $field = FormField::where('id', $request->field_id)->select('id', 'field_name', 'field_code', 'data_type','placeholder')->first();
        return response()->json([
            'field' => $field,
        ]);
    }
    public function admin_update_field(Request $request)
    {
        // Validate input và đảm bảo field_id tồn tại trong bảng form_fields
        $request->validate([
            'field_id'   => 'required|exists:form_fields,id',
            'field_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('form_fields')
                    ->ignore($request->field_id)
                    ->where(function ($query) use ($request) {
                        return $query->where('field_name', $request->field_name);
                    }),
            ],
            'field_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('form_fields')
                    ->ignore($request->field_id)
                    ->where(function ($query) use ($request) {
                        return $query->where('field_code', $request->field_code);
                    }),
            ],
            'placeholder' => 'nullable|string|max:255',
            'data_type'   => 'required|string|max:50',
        ], [
            'field_name.required' => 'Vui lòng nhập tên trường dữ liệu.',
            'field_name.unique'   => 'Tên trường dữ liệu đã tồn tại, vui lòng chọn tên khác.',
            'field_code.required' => 'Vui lòng nhập mã dữ liệu.',
            'field_code.unique'   => 'Mã dữ liệu đã tồn tại, vui lòng chọn mã khác.',
            'data_type.required'  => 'Vui lòng chọn kiểu dữ liệu.',
        ]);

        // Tìm bản ghi cần cập nhật theo field_id
        $formField = FormField::findOrFail($request->field_id);
        
        // Cập nhật dữ liệu
        $formField->update($request->only([ 'field_name', 'field_code', 'placeholder', 'data_type']));

        return response()->json([
            'status'     => true,
            'form_field' => $formField,
            'message'    => 'Trường dữ liệu đã được cập nhật thành công!'
        ]);
    }
    
    
    public function admin_delete_field(Request $request)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'field_id' => 'required|exists:form_fields,id'
        ], [
            'field_id.required' => 'Vui lòng chọn trường dữ liệu cần xóa.',
            'field_id.exists'   => 'Trường dữ liệu không tồn tại.'
        ]);

        DB::beginTransaction();
        try {
            // Tìm bản ghi FormField cần xóa
            $formField = FormField::findOrFail($request->field_id);
            $fieldCode = $formField->field_code; // Lấy giá trị field_code (so sánh chính xác)

            // Lấy tất cả các SupportForm có trường 'fields'
            $supportForms = SupportForm::whereNotNull('fields')->get();
            foreach ($supportForms as $supportForm) {
                // Chuyển đổi chuỗi JSON thành mảng
                $fieldsArray = json_decode($supportForm->fields, true);

                if (is_array($fieldsArray)) {
                    $originalCount = count($fieldsArray);
                    // Lọc bỏ phần tử có giá trị trùng khớp với $fieldCode (so sánh case sensitive)
                    $filteredArray = array_filter($fieldsArray, function ($item) use ($fieldCode) {
                        return $item !== $fieldCode;
                    });

                    // Nếu mảng sau khi lọc có số phần tử giảm, cập nhật lại trường 'fields'
                    if (count($filteredArray) !== $originalCount) {
                        // Re-index lại mảng và encode sang JSON
                        $supportForm->fields = json_encode(array_values($filteredArray));
                        $supportForm->save();
                    }
                }
            }

            // Xóa bản ghi trong bảng form_fields
            $formField->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Xóa trường dữ liệu thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Không thể xóa trường dữ liệu. Vui lòng thử lại sau!'
            ]);
        }
    }
}
