<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class TransferDocumentRequest extends FormRequest
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
            'target_type' => 'nullable|string',
            'to_user_ids' => 'nullable|array',
            'to_user_ids.*' => 'integer|distinct',
            'to_department_ids' => 'nullable|array',
            'to_department_ids.*' => 'integer|distinct',
            'to_branch_ids' => 'nullable|array',
            'to_branch_ids.*' => 'integer|distinct',
            'to_department_id' => 'nullable|integer',
            'note' => 'nullable|string|max:2000',
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
            'note.max' => 'Ghi chú chuyển tiếp không được vượt quá 2000 ký tự.',
        ];
    }
}
