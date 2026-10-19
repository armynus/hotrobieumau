<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
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
            'direction' => 'required|in:incoming,outgoing,decision,unclassified',
            'title' => 'required|string|max:5000',
            'registry_number' => 'nullable|string|max:255',
            'document_code' => 'required|string|max:255',
            'document_type_id' => 'nullable|integer|exists:document_types,id',
            'issued_date' => 'nullable|date',
            'received_date' => 'nullable|date',
            'forwarded_date' => 'nullable|date',
            'issuing_agency' => 'nullable|string|max:255',
            'signer' => 'nullable|string|max:255',
            'recipient' => 'nullable|string|max:5000',
            'archive_recipient' => 'nullable|string|max:5000',
            'copy_count' => 'nullable|integer|min:1|max:100000',
            'receipt_signature' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'priority' => 'required|in:normal,urgent,very_urgent',
            'security_level' => 'required|in:normal,confidential,secret,top_secret',
            'is_public_level' => 'required|in:private,normal,public',
            'sync_recipients' => 'sometimes|boolean',
            'to_user_ids' => 'nullable|array',
            'to_user_ids.*' => 'integer|distinct',
            'to_department_ids' => 'nullable|array',
            'to_department_ids.*' => 'integer|distinct',
            'to_branch_ids' => 'nullable|array',
            'to_branch_ids.*' => 'integer|distinct',
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
            'title.required' => 'Vui lòng nhập trích yếu nội dung văn bản.',
            'document_code.required' => 'Vui lòng nhập số, ký hiệu văn bản.',
            'priority.required' => 'Vui lòng chọn độ khẩn.',
            'security_level.required' => 'Vui lòng chọn mức độ bảo mật.',
            'is_public_level.required' => 'Vui lòng chọn phạm vi xem.',
        ];
    }
}
