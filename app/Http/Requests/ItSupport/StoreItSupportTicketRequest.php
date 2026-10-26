<?php

namespace App\Http\Requests\ItSupport;

use Illuminate\Foundation\Http\FormRequest;

class StoreItSupportTicketRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:10|max:5000',
            'contact_phone' => 'nullable|string|max:20',
            'category' => 'nullable|in:Phần cứng,Phần mềm,Mạng,Khác',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|extensions:pdf,doc,docx,jpg,jpeg,png|max:20480',
            'auto_attachment' => 'nullable|string',
            'auto_attachment_name' => 'nullable|string|max:255',
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
            'title.required' => 'Vui lòng nhập tiêu đề yêu cầu hỗ trợ.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'description.required' => 'Vui lòng mô tả chi tiết yêu cầu hỗ trợ.',
            'description.min' => 'Nội dung mô tả phải có ít nhất 10 ký tự.',
            'description.max' => 'Nội dung mô tả không được vượt quá 5000 ký tự.',
            'contact_phone.max' => 'Số điện thoại liên hệ không được vượt quá 20 ký tự.',
            'category.in' => 'Danh mục sự cố không hợp lệ.',
            'attachments.max' => 'Chỉ được đính kèm tối đa 5 tệp tin.',
            'attachments.*.file' => 'Tệp đính kèm không hợp lệ.',
            'attachments.*.extensions' => 'Định dạng tệp đính kèm không hỗ trợ (chỉ nhận PDF, DOC, DOCX, JPG, JPEG, PNG).',
            'attachments.*.mimes' => 'Định dạng tệp đính kèm không hỗ trợ (chỉ nhận PDF, DOC, DOCX, JPG, JPEG, PNG).',
            'attachments.*.max' => 'Dung lượng mỗi tệp đính kèm không vượt quá 20MB.',
        ];
    }
}
