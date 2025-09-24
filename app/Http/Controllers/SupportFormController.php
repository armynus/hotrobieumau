<?php

namespace App\Http\Controllers;

use App\Models\SupportForm;
use App\Models\FormType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class SupportFormController extends Controller
{
    
    /**
     * Trả về slug folder dựa trên type_name trong DB
     */
    private function getFormTypeFolderName(int $formTypeId): string
    {
        $formType = FormType::find($formTypeId);
        if ($formType && $formType->type_name) {
            return Str::slug($formType->type_name);
        }
        return 'khac';
    }

    public function support_forms_create(Request $request)
    {
        // Parse selected_fields JSON -> array
        $selectedFields = $request->input('selected_fields');
        if (!is_array($selectedFields)) {
            $selectedFields = json_decode($selectedFields, true) ?: [];
            $request->merge(['selected_fields' => $selectedFields]);
        }

        // Validation nhanh gọn
        $request->validate([
            'form_name'       => 'required|string|max:255',
            'form_type'       => 'required|integer|exists:form_type,id',
            'form_file'       => 'required|file|mimes:doc,docx|max:5120',
            'selected_fields' => 'required|array|min:1',
        ]);
        
        // Đảm bảo tên form chưa xài
        if (SupportForm::where('name', 'like', '%' . $request->form_name)->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'Tên biểu mẫu đã tồn tại. Đổi tên đi bro.'
            ]);
        }

        // Tính folder slug
        $folderName  = $this->getFormTypeFolderName($request->form_type);
        $directory   = "forms/supportform/{$folderName}/";
        $fileName    = $request->file('form_file')->getClientOriginalName();
        $filePath    = $directory . $fileName;

        if (Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'status'  => false,
                'message' => 'File đã tồn tại. Đổi tên hoặc chọn file khác nhé.'
            ]);
        }

        // Lưu file
        $request->file('form_file')->storeAs($directory, $fileName, 'public');

        // Tạo record
        $supportForm = SupportForm::create([
            'name'          => $request->form_name,
            'form_type'     => $request->form_type,
            'file_template' => $filePath,
            'fields'        => json_encode($selectedFields),
        ]);

        // Load formType relationship để trả về
        $supportForm = SupportForm::with('formType:id,type_name')
            ->find($supportForm->id);

        return response()->json([
            'status'  => true,
            'message' => 'Đã lưu form thành công!',
            'data'    => $supportForm
        ]);
    }

    public function editform(int $id)
    {
        try {
            $form = SupportForm::findOrFail($id);
            return response()->json(['status' => true, 'data' => $form]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy biểu mẫu!'
            ], 404);
        }
    }

    public function update(Request $request)
    {
        Log::info('Ajax payload:', $request->all());
        $id = $request->form_id;

        $request->validate([
            'form_name'       => 'required|string|max:255',
            'form_type'       => 'required|integer|exists:form_type,id',
            'form_file'       => 'nullable|file|mimes:doc,docx|max:5120',
            'selected_fields' => 'required|array|min:1',
        ]);

        $form    = SupportForm::findOrFail($id);
        $oldPath = $form->file_template;
        $oldType = $form->form_type;
        $newType = $request->form_type;

        // Tên form trùng?
        if (SupportForm::where('name', 'like', "%{$request->form_name}%")
            ->where('id', '!=', $id)
            ->exists()
        ) {
            return response()->json([
                'status'  => false,
                'message' => 'Tên biểu mẫu đã tồn tại, chọn cái khác nhé.'
            ], 400);
        }

        // Folder mới theo slug
        $newFolder    = $this->getFormTypeFolderName($newType);
        $newDirectory = "forms/supportform/{$newFolder}/";

        // 1) Có file mới => replace
        if ($request->hasFile('form_file')) {
            $newName = $request->file('form_file')->getClientOriginalName();
            $newPath = $newDirectory . $newName;

            // 🔍 Check nếu file đã tồn tại, nhưng exclude luôn file cũ của form
            if (Storage::disk('public')->exists($newPath) && $newPath !== $oldPath) {
                return response()->json([
                    'status'  => false,
                    'message' => 'File đã tồn tại. Đổi tên hoặc chọn file khác nhé.'
                ], 400);
            }

            // Xóa file cũ nếu khác đường dẫn mới
            if ($oldPath && Storage::disk('public')->exists($oldPath) && $newPath !== $oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            // Lưu file mới
            $request->file('form_file')->storeAs($newDirectory, $newName, 'public');
            $form->file_template = $newPath;

        // 2) Không upload file nhưng đổi type => move
        } elseif ($oldType !== $newType) {
            $baseName = basename($oldPath);
            $movedTo  = $newDirectory . $baseName;

            // Exclude nếu target path trùng với oldPath
            if ($oldPath !== $movedTo && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->move($oldPath, $movedTo);
                $form->file_template = $movedTo;
            }
        }

        // Cập nhật các field còn lại
        $form->name      = $request->form_name;
        $form->form_type = $newType;
        $form->fields    = json_encode($request->input('selected_fields'));
        $form->save();

        $form = SupportForm::with('formType:id,type_name')->find($id);

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật form thành công!',
            'data'    => $form
        ]);
    }


    public function destroy(int $id)
    {
        try {
            $form = SupportForm::findOrFail($id);

            if (Storage::disk('public')->exists($form->file_template)) {
                Storage::disk('public')->delete($form->file_template);
            }

            $form->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Xóa form thành công!'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Xóa thất bại: ' . $e->getMessage()
            ], 500);
        }
    }
}
