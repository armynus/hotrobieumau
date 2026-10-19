<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return session()->has('user_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'direction' => 'required|in:incoming,outgoing,decision',
            'title' => 'required|string|max:5000',
            'registry_number' => 'nullable|string|max:255',
            'document_code' => 'required|string|max:255',
            'document_type_id' => 'nullable|integer|exists:document_types,id',
            'issued_date' => 'required|date',
            'received_date' => 'required_if:direction,incoming|nullable|date',
            'forwarded_date' => 'required_if:direction,outgoing,decision|nullable|date',
            'issuing_agency' => 'nullable|string|max:255',
            'signer' => 'nullable|string|max:255',
            'recipient' => 'nullable|string|max:5000',
            'archive_recipient' => 'nullable|string|max:5000',
            'copy_count' => 'nullable|integer|min:1|max:100000',
            'receipt_signature' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'priority' => 'nullable|in:normal,urgent,very_urgent',
            'security_level' => 'nullable|in:normal,confidential,secret,top_secret',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200', // 50MB max per file
            'is_public_level' => 'required|in:private,normal,public',
            'to_user_ids' => 'nullable|array',
            'to_user_ids.*' => 'integer|distinct',
            'to_department_ids' => 'nullable|array',
            'to_department_ids.*' => 'integer|distinct',
            'to_branch_ids' => 'nullable|array',
            'to_branch_ids.*' => 'integer|distinct',
            'allow_duplicate' => 'sometimes|boolean',
            'register_in_ledger' => 'sometimes|nullable|boolean',
            'ledger_entry_id' => 'sometimes|nullable|integer',
            'ledger_auto_number' => 'sometimes|nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'direction.required' => 'Vui lòng chọn phân loại văn bản.',
            'direction.in' => 'Phân loại văn bản không hợp lệ.',
            'title.required' => 'Vui lòng nhập trích yếu văn bản.',
            'document_code.required' => 'Vui lòng nhập số, ký hiệu văn bản.',
            'issued_date.required' => 'Vui lòng nhập ngày, tháng văn bản.',
            'issued_date.date' => 'Ngày, tháng văn bản không hợp lệ.',
            'is_public_level.required' => 'Vui lòng chọn mức độ công khai.',
            'files.*.max' => 'Dung lượng tệp đính kèm không được vượt quá 50MB.',
        ];
    }
}
