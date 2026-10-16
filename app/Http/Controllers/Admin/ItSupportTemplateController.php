<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItSupportCategory;
use App\Models\ItSupportFormField;
use App\Models\ItSupportTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ItSupportTemplateController extends Controller
{
    public function index()
    {
        $templates = ItSupportTemplate::with('category')->orderBy('name')->get();
        $it_fields = ItSupportFormField::orderBy('display_order', 'asc')->get();
        $it_content_groups = ItSupportFormField::CONTENT_GROUPS;
        $categories = ItSupportCategory::ordered()->get();

        return view('admin.it_support.templates', compact('templates', 'it_fields', 'it_content_groups', 'categories'));
    }

    public function parse_tags(Request $request)
    {
        $request->validate([
            'file'        => 'nullable|file|mimes:doc,docx|max:5120',
            'template_id' => 'nullable|exists:it_support_templates,id',
        ], [
            'file.mimes' => 'File mẫu phải có định dạng .docx hoặc .doc.',
        ]);

        try {
            $filePath = null;

            if ($request->hasFile('file')) {
                $extension = strtolower($request->file('file')->getClientOriginalExtension());
                if ($extension !== 'docx') {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Tính năng nhận diện tự động chỉ hỗ trợ file .docx. Vui lòng chọn trường dữ liệu thủ công.',
                    ]);
                }
                $filePath = $request->file('file')->getPathname();
            } elseif ($request->filled('template_id')) {
                $template = ItSupportTemplate::findOrFail($request->template_id);
                $rawPath = $template->file_template;

                if ($rawPath && Storage::disk('public')->exists($rawPath)) {
                    $filePath = Storage::disk('public')->path($rawPath);
                } elseif ($rawPath && file_exists(storage_path('app/public/' . $rawPath))) {
                    $filePath = storage_path('app/public/' . $rawPath);
                } elseif ($rawPath && file_exists($rawPath)) {
                    $filePath = realpath($rawPath) ?: $rawPath;
                }

                if (!$filePath || !file_exists($filePath)) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Không tìm thấy file mẫu trên hệ thống lưu trữ.',
                    ], 404);
                }
            } else {
                return response()->json([
                    'status'  => false,
                    'message' => 'Vui lòng chọn file mẫu hoặc chỉ định biểu mẫu cần quét.',
                ], 422);
            }

            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
            $variables = array_values(array_unique($templateProcessor->getVariables()));

            // Đối chiếu với danh sách trường hiện có trong từ điển trường IT
            $allFieldCodes = ItSupportFormField::pluck('field_code')->all();
            
            $matched = [];
            $unmatched = [];
            $fieldLookup = [];
            foreach ($allFieldCodes as $code) {
                $fieldLookup[$code] = $code;
                $fieldLookup[strtolower($code)] = $code;
                $fieldLookup[strtolower(str_replace('_', '', $code))] = $code;
            }

            // Ánh xạ các biến dẫn xuất (in hoa) hoặc alias về trường dữ liệu gốc trong từ điển IT
            $derivedAndAliasMap = [
                // Họ tên in hoa -> TenCanBo
                'tencanbohoa'    => 'TenCanBo',
                'canboyeucauhoa' => 'TenCanBo',
                'hotenhoa'       => 'TenCanBo',
                'tennvhoa'       => 'TenCanBo',
                'hoten'          => 'TenCanBo',
                'canboyeucau'    => 'TenCanBo',
                'canbo'          => 'TenCanBo',
                'tennv'          => 'TenCanBo',

                // Chi nhánh in hoa & alias -> TenChiNhanh
                'chinhanhhoa'    => 'TenChiNhanh',
                'tenchinhanhhoa' => 'TenChiNhanh',
                'branchnamehoa'  => 'TenChiNhanh',
                'branchhoa'      => 'TenChiNhanh',
                'chinhanh'       => 'TenChiNhanh',
                'branch'         => 'TenChiNhanh',
                'branchname'     => 'TenChiNhanh',
                'donvi'          => 'TenChiNhanh',
                'tendonvi'       => 'TenChiNhanh',

                // Các alias thông dụng khác
                'email'          => 'EmailNV',
                'emailcanbo'     => 'EmailNV',
                'emailnhanvien'  => 'EmailNV',
                'sdt'            => 'SoDienThoai',
                'phone'          => 'SoDienThoai',
                'dienthoai'      => 'SoDienThoai',
                'msnv'           => 'MaNV',
                'manhanvien'     => 'MaNV',
                'useripcas'      => 'IPCAS',
                'bophan'         => 'PhongBan',
                'department'     => 'PhongBan',
                'maphong'        => 'PhongBan',
                'maphongban'     => 'PhongBan',
                'chucdanh'       => 'ChucVu',
                'position'       => 'ChucVu',
                'ngaylap'        => 'NgayYeuCau',
                'ngaygiaodich'   => 'NgayYeuCau',
            ];

            foreach ($derivedAndAliasMap as $alias => $targetCode) {
                if (in_array($targetCode, $allFieldCodes, true) && !isset($fieldLookup[$alias])) {
                    $fieldLookup[$alias] = $targetCode;
                }
            }

            foreach ($variables as $v) {
                $cleanV = strtolower(str_replace('_', '', $v));
                if (isset($fieldLookup[$v])) {
                    $matched[] = $fieldLookup[$v];
                } elseif (isset($fieldLookup[strtolower($v)])) {
                    $matched[] = $fieldLookup[strtolower($v)];
                } elseif (isset($fieldLookup[$cleanV])) {
                    $matched[] = $fieldLookup[$cleanV];
                } else {
                    $unmatched[] = $v;
                }
            }

            $matched = array_values(array_unique($matched));
            $unmatched = array_values(array_unique($unmatched));

            return response()->json([
                'status'    => true,
                'data'      => $variables,
                'matched'   => $matched,
                'unmatched' => $unmatched,
                'message'   => 'Đã quét và phân tích các trường từ file Word thành công!',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Không thể đọc nội dung file: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function store(Request $request)
    {
        // Parse selected_fields nếu truyền dạng json string
        $selectedFields = $request->input('selected_fields', []);
        if (is_string($selectedFields)) {
            $selectedFields = json_decode($selectedFields, true) ?: [];
        }

        $request->validate([
            'name'            => 'required|string|max:255|unique:it_support_templates,name',
            'category_id'     => 'nullable|exists:it_support_categories,id',
            'form_file'       => 'required|file|mimes:doc,docx|max:5120',
            'selected_fields' => 'nullable|array',
        ], [
            'name.required'        => 'Vui lòng nhập tên biểu mẫu IT.',
            'name.unique'          => 'Tên biểu mẫu IT đã tồn tại.',
            'category_id.exists'   => 'Danh mục đã chọn không hợp lệ.',
            'form_file.required'   => 'Vui lòng chọn file mẫu Word (.docx).',
            'form_file.mimes'      => 'File mẫu phải có định dạng .doc hoặc .docx.',
        ]);

        $directory = "it_support_templates/";
        $fileName  = time() . '_' . $request->file('form_file')->getClientOriginalName();
        $filePath  = $directory . $fileName;

        $request->file('form_file')->storeAs($directory, $fileName, 'public');

        // Nếu admin chưa tick chọn trường nào thủ công, tự động parse từ file Word để fallback
        if (empty($selectedFields)) {
            try {
                $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('app/public/' . $filePath));
                $selectedFields = $templateProcessor->getVariables();
            } catch (\Throwable $e) {
                $selectedFields = [];
            }
        }

        $selectedFields = array_values(array_unique(array_filter($selectedFields)));

        ItSupportTemplate::create([
            'category_id'   => $request->filled('category_id') ? $request->category_id : null,
            'name'          => $request->name,
            'file_template' => $filePath,
            'fields'        => $selectedFields,
        ]);

        return redirect()->route('admin.it_support.templates')->with('success', 'Đã thêm biểu mẫu IT thành công!');
    }

    public function update(Request $request, $id)
    {
        $template = ItSupportTemplate::findOrFail($id);

        $selectedFields = $request->input('selected_fields', []);
        if (is_string($selectedFields)) {
            $selectedFields = json_decode($selectedFields, true) ?: [];
        }

        $request->validate([
            'name'            => 'required|string|max:255|unique:it_support_templates,name,' . $id,
            'category_id'     => 'nullable|exists:it_support_categories,id',
            'form_file'       => 'nullable|file|mimes:doc,docx|max:5120',
            'selected_fields' => 'nullable|array',
        ], [
            'name.required'      => 'Vui lòng nhập tên biểu mẫu IT.',
            'name.unique'        => 'Tên biểu mẫu IT đã tồn tại.',
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
        ]);

        if ($request->hasFile('form_file')) {
            $directory = "it_support_templates/";
            $fileName  = time() . '_' . $request->file('form_file')->getClientOriginalName();
            $newPath   = $directory . $fileName;

            $request->file('form_file')->storeAs($directory, $fileName, 'public');

            if ($template->file_template && Storage::disk('public')->exists($template->file_template)) {
                Storage::disk('public')->delete($template->file_template);
            }
            $template->file_template = $newPath;

            // Nếu không chọn fields mới mà có file mới, tự động parse lại
            if (!$request->has('selected_fields')) {
                try {
                    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('app/public/' . $newPath));
                    $selectedFields = $templateProcessor->getVariables();
                } catch (\Throwable $e) {
                    $selectedFields = $template->fields;
                }
            }
        }

        $template->fields = is_array($selectedFields) ? array_values(array_unique(array_filter($selectedFields))) : [];
        $template->name = $request->name;
        $template->category_id = $request->filled('category_id') ? $request->category_id : null;
        $template->save();

        if ($request->ajax() || $request->wantsJson()) {
            $template->load('category');
            $allFields = ItSupportFormField::whereIn('field_code', $template->fields ?: [])->get()->keyBy('field_code');
            $fieldsDetail = [];
            foreach (($template->fields ?: []) as $fc) {
                $f = $allFields->get($fc);
                $fieldsDetail[] = [
                    'code' => $fc,
                    'name' => $f ? $f->field_name : $fc,
                ];
            }

            return response()->json([
                'status'   => true,
                'message'  => 'Cập nhật biểu mẫu IT thành công!',
                'template' => [
                    'id'            => $template->id,
                    'name'          => $template->name,
                    'category_id'   => $template->category_id,
                    'category_name' => $template->category ? $template->category->name : 'Chưa phân loại',
                    'category_icon' => $template->category ? ($template->category->icon ?: 'fas fa-folder') : 'fas fa-folder',
                    'category_desc' => $template->category ? $template->category->description : '',
                    'file_name'     => basename($template->file_template),
                    'fields'        => $template->fields ?: [],
                    'fields_detail' => $fieldsDetail,
                    'usage_count'   => $template->usage_count,
                    'updated_at'    => $template->updated_at->format('d/m/Y H:i'),
                ],
            ]);
        }

        return redirect()->route('admin.it_support.templates')->with('success', 'Cập nhật biểu mẫu IT thành công!');
    }

    public function destroy($id)
    {
        try {
            $template = ItSupportTemplate::findOrFail($id);

            if ($template->file_template && Storage::disk('public')->exists($template->file_template)) {
                Storage::disk('public')->delete($template->file_template);
            }

            $template->delete();

            return back()->with('success', 'Xóa biểu mẫu IT thành công!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi khi xóa biểu mẫu: ' . $e->getMessage());
        }
    }
}
