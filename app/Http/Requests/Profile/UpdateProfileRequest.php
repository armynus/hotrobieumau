<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $userId = (int) session('user_id');

        return [
            'citizen_id' => ['nullable', 'string', 'regex:/^\d{9,20}$/', Rule::unique('users', 'citizen_id')->ignore($userId)],
            'phone_no' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+() ._-]{8,20}$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=80,min_height=80,max_width=4000,max_height=4000'],
            'remove_avatar' => ['nullable', 'boolean'],
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
            'citizen_id.regex' => 'Số CCCD phải gồm từ 9 đến 20 chữ số.',
            'citizen_id.unique' => 'Số CCCD này đã được sử dụng bởi tài khoản khác.',
            'phone_no.regex' => 'Số điện thoại không đúng định dạng.',
            'phone_no.max' => 'Số điện thoại không được quá 20 ký tự.',
            'avatar.image' => 'Tệp đã chọn không phải là hình ảnh hợp lệ.',
            'avatar.mimes' => 'Ảnh đại diện chỉ hỗ trợ JPG, PNG hoặc WebP.',
            'avatar.max' => 'Ảnh đại diện không được lớn hơn 2 MB.',
            'avatar.dimensions' => 'Ảnh phải từ 80×80 đến 4000×4000 pixel.',
        ];
    }
}
