<?php

namespace App\Http\Requests\ItSupport;

use Illuminate\Foundation\Http\FormRequest;

class ReplyItSupportTicketRequest extends FormRequest
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
            'message' => 'required|string|min:3|max:3000',
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
            'message.required' => 'Vui lòng nhập nội dung phản hồi.',
            'message.min' => 'Nội dung phản hồi phải có ít nhất 3 ký tự.',
            'message.max' => 'Nội dung phản hồi không được vượt quá 3000 ký tự.',
        ];
    }
}
