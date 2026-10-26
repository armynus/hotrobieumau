<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItSupportFormField;
use App\Models\ItSupportTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItSupportFormFieldController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'field_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('it_support_form_fields')->where(function ($query) use ($request) {
                    return $query->where('field_name', $request->field_name);
                }),
            ],
            'field_code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('it_support_form_fields')->where(function ($query) use ($request) {
                    return $query->where('field_code', $request->field_code);
                }),
            ],
            'placeholder'   => 'nullable|string|max:255',
            'value'         => 'nullable|string|max:255',
            'data_type'     => 'required|string|max:50',
            'content_group' => ['required', Rule::in(array_keys(ItSupportFormField::CONTENT_GROUPS))],
            'display_order' => 'required|integer|min:0|max:9999',
        ], [
            'field_name.required'    => 'Vui lòng nhập tên trường dữ liệu.',
            'field_name.unique'      => 'Tên trường dữ liệu IT đã tồn tại, vui lòng chọn tên khác.',
            'field_code.required'    => 'Vui lòng nhập mã trường dữ liệu.',
            'field_code.regex'       => 'Mã trường chỉ được chứa chữ cái, số và dấu gạch dưới (không dấu, không khoảng trắng).',
            'field_code.unique'      => 'Mã trường dữ liệu IT đã tồn tại, vui lòng chọn mã khác.',
            'data_type.required'     => 'Vui lòng chọn kiểu dữ liệu.',
            'content_group.required' => 'Vui lòng chọn nhóm nội dung.',
            'content_group.in'       => 'Nhóm nội dung không hợp lệ.',
            'display_order.required' => 'Vui lòng nhập thứ tự hiển thị.',
            'display_order.integer'  => 'Thứ tự hiển thị phải là số nguyên.',
        ]);

        $data = $request->only(['field_name', 'field_code', 'placeholder', 'data_type', 'value', 'content_group', 'display_order']);
        if (preg_match('/(_HOA|HOA)$/i', $data['field_code']) || str_contains(mb_strtolower($data['field_name'], 'UTF-8'), 'in hoa') || str_contains(mb_strtolower($data['field_name'], 'UTF-8'), 'viết hoa')) {
            $data['data_type'] = 'uppercase';
        }

        $formField = ItSupportFormField::create($data);

        return response()->json([
            'status'     => true,
            'form_field' => $formField,
            'message'    => 'Trường dữ liệu IT đã được thêm thành công!',
        ]);
    }

    public function edit(Request $request)
    {
        $field = ItSupportFormField::where('id', $request->field_id)->first();
        if (!$field) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy trường dữ liệu!'], 404);
        }

        return response()->json([
            'status' => true,
            'field'  => $field,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'field_id'   => 'required|exists:it_support_form_fields,id',
            'field_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('it_support_form_fields')
                    ->ignore($request->field_id)
                    ->where(function ($query) use ($request) {
                        return $query->where('field_name', $request->field_name);
                    }),
            ],
            'field_code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('it_support_form_fields')
                    ->ignore($request->field_id)
                    ->where(function ($query) use ($request) {
                        return $query->where('field_code', $request->field_code);
                    }),
            ],
            'placeholder'   => 'nullable|string|max:255',
            'value'         => 'nullable|string|max:255',
            'data_type'     => 'required|string|max:50',
            'content_group' => ['required', Rule::in(array_keys(ItSupportFormField::CONTENT_GROUPS))],
            'display_order' => 'required|integer|min:0|max:9999',
        ], [
            'field_name.required'    => 'Vui lòng nhập tên trường dữ liệu.',
            'field_name.unique'      => 'Tên trường dữ liệu IT đã tồn tại, vui lòng chọn tên khác.',
            'field_code.required'    => 'Vui lòng nhập mã dữ liệu.',
            'field_code.regex'       => 'Mã trường chỉ được chứa chữ cái, số và dấu gạch dưới (không dấu, không khoảng trắng).',
            'field_code.unique'      => 'Mã dữ liệu IT đã tồn tại, vui lòng chọn mã khác.',
            'data_type.required'     => 'Vui lòng chọn kiểu dữ liệu.',
            'content_group.required' => 'Vui lòng chọn nhóm nội dung.',
            'content_group.in'       => 'Nhóm nội dung không hợp lệ.',
            'display_order.required' => 'Vui lòng nhập thứ tự hiển thị.',
            'display_order.integer'  => 'Thứ tự hiển thị phải là số nguyên.',
        ]);

        $formField = ItSupportFormField::findOrFail($request->field_id);
        $oldCode = $formField->field_code;
        $newCode = $request->field_code;

        $data = $request->only(['field_name', 'field_code', 'placeholder', 'data_type', 'value', 'content_group', 'display_order']);
        if (preg_match('/(_HOA|HOA)$/i', $data['field_code']) || str_contains(mb_strtolower($data['field_name'], 'UTF-8'), 'in hoa') || str_contains(mb_strtolower($data['field_name'], 'UTF-8'), 'viết hoa')) {
            $data['data_type'] = 'uppercase';
        }

        $formField->update($data);

        // Nếu mã trường thay đổi, đồng bộ cập nhật trong tất cả các biểu mẫu đang sử dụng mã cũ
        if ($oldCode && $newCode && $oldCode !== $newCode) {
            $templates = \App\Models\ItSupportTemplate::all();
            foreach ($templates as $template) {
                if (is_array($template->fields) && in_array($oldCode, $template->fields, true)) {
                    $updatedFields = array_map(fn($c) => $c === $oldCode ? $newCode : $c, $template->fields);
                    $template->update(['fields' => array_values(array_unique($updatedFields))]);
                }
            }
        }

        return response()->json([
            'status'     => true,
            'form_field' => $formField,
            'message'    => 'Trường dữ liệu IT đã được cập nhật thành công!',
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'field_id' => 'required|exists:it_support_form_fields,id'
        ], [
            'field_id.required' => 'Vui lòng chọn trường dữ liệu cần xóa.',
            'field_id.exists'   => 'Trường dữ liệu không tồn tại.'
        ]);

        DB::beginTransaction();
        try {
            $formField = ItSupportFormField::findOrFail($request->field_id);
            $fieldCode = $formField->field_code;

            // Xóa mã trường này khỏi tất cả các biểu mẫu IT đang sử dụng
            $templates = ItSupportTemplate::whereNotNull('fields')->get();
            foreach ($templates as $template) {
                $fieldsArray = is_array($template->fields) ? $template->fields : json_decode($template->fields, true);
                if (is_array($fieldsArray)) {
                    $originalCount = count($fieldsArray);
                    $filtered = array_filter($fieldsArray, fn($item) => $item !== $fieldCode);
                    if (count($filtered) !== $originalCount) {
                        $template->fields = array_values($filtered);
                        $template->save();
                    }
                }
            }

            $formField->delete();
            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Xóa trường dữ liệu IT thành công!'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi khi xóa trường dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }
}
