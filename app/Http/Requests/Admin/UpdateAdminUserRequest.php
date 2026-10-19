<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('role_ids') && is_array($this->input('role_ids'))) {
            $roleIds = array_filter($this->input('role_ids'));
            if (!empty($roleIds) && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
                $slugs = \App\Models\Role::whereIn('id', $roleIds)->pluck('slug')->toArray();
                $derivedRoleId = 2;
                if (in_array('admin', $slugs, true)) {
                    $derivedRoleId = 0;
                } elseif (in_array('supervisor', $slugs, true)) {
                    $derivedRoleId = 1;
                }
                $derivedDocRole = in_array('clerk', $slugs, true) ? 'clerk' : 'user';

                $this->merge([
                    'role_id' => $this->input('role_id', $derivedRoleId) ?? $derivedRoleId,
                    'document_role' => $this->input('document_role', $derivedDocRole) ?? $derivedDocRole,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->integer('user_id');
        $branchId = $this->integer('branch_id');
        $user = User::find($userId);
        $allowedRoles = $user && $user->role_id == 0 ? ['0', '1', '2', 0, 1, 2] : ['0', '1', '2', 0, 1, 2];

        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'citizen_id' => ['nullable', 'string', 'max:20', Rule::unique('users', 'citizen_id')->ignore($userId)],
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_code')->ignore($userId)],
            'phone_no' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+() ._-]{8,20}$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'user_ipcas' => ['nullable', 'string', 'max:50', Rule::unique('users', 'user_ipcas')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('branch_id', $branchId)),
            ],
            'transaction_office_id' => [
                'nullable',
                'integer',
                Rule::exists('transaction_offices', 'id')->where(function ($query) use ($branchId, $user): void {
                    $query->where('branch_id', $branchId)
                        ->where(function ($statusQuery) use ($user): void {
                            $statusQuery->where('status', 'active');
                            if ($user?->transaction_office_id) {
                                $statusQuery->orWhere('id', $user->transaction_office_id);
                            }
                        });
                }),
            ],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer'],
            'role_id' => ['required', Rule::in($allowedRoles)],
            'document_role' => ['required', Rule::in(['user', 'clerk'])],
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
            'user_id.required' => 'Mã người dùng không hợp lệ.',
            'name.required' => 'Vui lòng nhập họ và tên.',
            'citizen_id.unique' => 'Số CCCD đã được sử dụng bởi tài khoản khác.',
            'employee_code.unique' => 'Mã nhân viên đã được sử dụng bởi tài khoản khác.',
            'phone_no.regex' => 'Số điện thoại không đúng định dạng.',
            'phone_no.max' => 'Số điện thoại không được quá 20 ký tự.',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
            'user_ipcas.unique' => 'Mã người dùng IPCAS đã được sử dụng.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'branch_id.required' => 'Vui lòng chọn chi nhánh.',
            'branch_id.exists' => 'Chi nhánh đã chọn không tồn tại.',
            'department_id.exists' => 'Phòng ban không thuộc chi nhánh đã chọn.',
            'transaction_office_id.exists' => 'Phòng giao dịch không hoạt động hoặc không thuộc chi nhánh đã chọn.',
            'position_id.exists' => 'Chức vụ đã chọn không tồn tại.',
            'role_id.required' => 'Vui lòng chọn ít nhất một vai trò cho tài khoản.',
            'role_id.in' => 'Vai trò tài khoản không hợp lệ.',
            'document_role.required' => 'Vui lòng chọn vai trò văn bản.',
            'document_role.in' => 'Vai trò văn bản không hợp lệ.',
        ];
    }
}
