<?php

namespace App\Http\Controllers;
use App\Models\Branches;
use App\Models\SupportForm;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SupportFormController extends Controller
{
    public function support_forms_create(Request $request){
        $selectedFields = $request->input('selected_fields');
        if (!is_array($selectedFields)) {
            $selectedFields = json_decode($selectedFields, true);
            $request->merge(['selected_fields' => $selectedFields]);
        }
        // Kiểm tra validation
        $request->validate([
            'form_name' => 'required|string|max:255',
            'form_file' => 'required|file|mimes:doc,docx|max:5120', // Giới hạn 5MB
            'selected_fields' => 'required|array|min:1',
        ]);
        // Kiểm tra nếu tên form đã tồn tại trong database
        if (SupportForm::where('name', 'like', '%' . $request->form_name)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Tên biểu mẫu đã tồn tại trong cơ sở dữ liệu. Vui lòng đổi tên hoặc chọn tên khác.'
            ]);
        }
        // Đường dẫn lưu trữ
        $directory = 'forms/supportform/';
        $fileName = $request->file('form_file')->getClientOriginalName();
        $filePath = $directory . $fileName;

        // Kiểm tra nếu file đã tồn tại
        if (Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'status' => false,
                'message' => 'File đã tồn tại. Vui lòng đổi tên hoặc chọn file khác.'
            ]);
        }

        // Lưu file vào storage
        $request->file('form_file')->storeAs($directory, $fileName, 'public');

        // Lưu dữ liệu vào database
        $supportForm = SupportForm::create([
            'name' => $request->form_name,
            'file_template' => $filePath,
            'fields' => json_encode($request->selected_fields),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Biểu mẫu đã được lưu thành công!',
            'data' => $supportForm
        ]);
    }
    public function editform($id)
    {
        try {
            $form = SupportForm::findOrFail($id);
    
            return response()->json([
                'status' => true,
                'data' => $form
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy biểu mẫu!'
            ], 404);
        }
    }
    public function update(Request $request)
    {
        Log::info('Dữ liệu nhận được từ Ajax:', $request->all());
        $id = $request->form_id;
      
        try {
            $selectedFields = $request->input('selected_fields', []);
            
            $request->validate([
                'form_name' => 'required|string|max:255',
                'form_file' => 'nullable|file|mimes:doc,docx|max:5120',
                'selected_fields' => 'required|array|min:1',
            ]);

            $form = SupportForm::findOrFail($id);
            
            // Kiểm tra nếu tên biểu mẫu đã tồn tại nhưng không phải của chính nó
            if (SupportForm::where('name', 'like', '%' . $request->form_name)
                ->where('id', '!=', $id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tên biểu mẫu đã tồn tại, vui lòng chọn tên khác.'
                ], 400);
            }

             // Nếu có file mới, kiểm tra xem file đã tồn tại chưa
            if ($request->hasFile('form_file')) {
                $directory = 'forms/supportform/';
                $fileName = $request->file('form_file')->getClientOriginalName();
                $filePath = $directory . $fileName;

                // 🔍 Kiểm tra nếu file đã tồn tại
                if (Storage::disk('public')->exists($filePath)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'File đã tồn tại. Vui lòng đổi tên hoặc chọn file khác.'
                    ], 400);
                }

                // Xóa file cũ nếu có
                if ($form->file_template) {
                    Storage::disk('public')->delete($form->file_template);
                }

                // Lưu file mới
                $request->file('form_file')->storeAs($directory, $fileName, 'public');
                $form->file_template = $filePath;
            }


            // Cập nhật dữ liệu
            $form->name = $request->form_name;
            $form->fields = json_encode($selectedFields);
            $form->save();

            return response()->json([
                'status' => true,
                'message' => 'Biểu mẫu đã được cập nhật thành công!',
                'data' => $form
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật thất bại: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $form = SupportForm::findOrFail($id);

            // Delete the file from storage
            if (Storage::disk('public')->exists($form->file_template)) {
                Storage::disk('public')->delete($form->file_template);
            }

            // Delete the form from the database
            $form->delete();

            return response()->json([
                'status' => true,
                'message' => 'Biểu mẫu đã được xóa thành công.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Xóa biểu mẫu thất bại: ' . $e->getMessage()
            ], 500);
     
        }
    }
}
