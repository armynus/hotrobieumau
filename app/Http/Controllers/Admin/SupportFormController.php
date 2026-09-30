<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

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


    public function parse_tags(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:doc,docx|max:5120',
        ]);

        try {
            $extension = strtolower($request->file('file')->getClientOriginalExtension());
            if ($extension !== 'docx') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tính năng nhận diện tự động chỉ hỗ trợ file .docx. Vui lòng chọn trường dữ liệu thủ công.'
                ]);
            }

            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($request->file('file')->getPathname());
            $variables = $templateProcessor->getVariables();
            $variables = array_values(array_unique($variables));

            $mappedFields = $variables;
            
            // Typical mappings based on DocumentService
            $map = [
                'Check_NAM' => 'gender', 'Check_NU' => 'gender',
                'ccvc' => 'NgheNghiepKH', 'cabd' => 'NgheNghiepKH', 'gvbs' => 'NgheNghiepKH', 'ks' => 'NgheNghiepKH',
                'cn' => 'NgheNghiepKH', 'nd' => 'NgheNghiepKH', 'lsncm' => 'NgheNghiepKH', 'kdtd' => 'NgheNghiepKH',
                'hdvtvhk' => 'NgheNghiepKH', 'ctgd' => 'NgheNghiepKH', 'hssv' => 'NgheNghiepKH', 'nt' => 'NgheNghiepKH', 'nnkhac' => 'NgheNghiepKH',
                'ChucVu_CTGD' => 'ChucVuKH', 'ChucVu_CBNV' => 'ChucVuKH', 'ChucVu_CTTD' => 'ChucVuKH', 'ChucVu_QLCT' => 'ChucVuKH', 'ChucVu_Khac' => 'ChucVuKH',
                'Check_TheND' => 'LoaiThe', 'Check_TheNapas' => 'LoaiThe', 'Check_TheJCB' => 'LoaiThe', 'Check_TheTH' => 'LoaiThe', 'Check_TheVS' => 'LoaiThe', 'Check_TheMT' => 'LoaiThe', 'Check_TheKHAC' => 'LoaiThe',
                'Check_VND' => 'ccycd', 'Check_USD' => 'ccycd', 'Check_EUR' => 'ccycd', 'Check_TienKhac' => 'ccycd',
                'LoaiTK_Auto' => 'SoTKTT', 'LoaiTK_Chon' => 'SoTKTT', 'LoaiTK_ChDung' => 'SoTKTT',
                'Check_Vang' => 'HangThe', 'Check_Chuan' => 'HangThe',
                'Check_Nuoc' => 'ThuTuDong', 'Check_Dien' => 'ThuTuDong', 'Check_VienT' => 'ThuTuDong', 'Check_HocP' => 'ThuTuDong', 'Check_BH' => 'ThuTuDong',
                'MB_APLUS' => 'MobileBanking', 'MB_EC' => 'MobileBanking', 'MB_SMS' => 'MobileBanking', 'MB_VDT' => 'MobileBanking', 'MB_BPLUS' => 'MobileBanking',
                'EBANK_Mobile' => 'RetaileBanking', 'EBANK_Internet' => 'RetaileBanking', 'Goi_PTC' => 'RetaileBanking', 'Goi_TC' => 'RetaileBanking', 'Goi_SMS' => 'RetaileBanking', 'Goi_Soft' => 'RetaileBanking', 'Goi_Token' => 'RetaileBanking',
                'DV_VV' => 'DichVuKhac', 'DV_TK' => 'DichVuKhac', 'DV_KH' => 'DichVuKhac', 'DV_CTNN' => 'DichVuKhac', 'DV_MBNT' => 'DichVuKhac', 'DV_BH' => 'DichVuKhac', 'DV_KHAC' => 'DichVuKhac',
            ];
            foreach($variables as $var) {
                if (isset($map[$var])) {
                    $mappedFields[] = $map[$var];
                }
            }
            $mappedFields = array_values(array_unique($mappedFields));

            return response()->json([
                'status'  => true,
                'data'    => $mappedFields,
                'message' => 'Đã phân tích các trường từ file Word thành công!'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Không thể đọc nội dung file: ' . $e->getMessage()
            ], 400);
        }
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
