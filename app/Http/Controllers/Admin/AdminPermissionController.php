<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminPermissionController extends Controller
{
    /**
     * Store a newly created permission in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'unique:permissions,slug', 'regex:/^[a-z0-9_.-]+$/'],
            'module' => ['required', 'string', 'max:50'],
            'module_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'assign_to_admin' => ['nullable'],
        ], [
            'name.required' => 'Vui lòng nhập tên quyền hạn.',
            'slug.required' => 'Mã quyền định danh (slug) không được để trống.',
            'slug.unique' => 'Mã quyền định danh này đã tồn tại trong hệ thống.',
            'slug.regex' => 'Mã quyền chỉ được chứa chữ thường, số, dấu chấm, gạch ngang và gạch dưới (VD: data.export_advanced).',
            'module.required' => 'Vui lòng chọn hoặc nhập mã phân hệ.',
            'module_name.required' => 'Vui lòng nhập tên phân hệ hiển thị.',
        ]);

        $permission = Permission::create([
            'name' => trim($validated['name']),
            'slug' => strtolower(trim($validated['slug'])),
            'module' => strtolower(trim($validated['module'])),
            'module_name' => trim($validated['module_name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_system' => false,
        ]);

        // Mặc định tự động gán quyền mới cho vai trò Quản trị viên (admin) để test & sử dụng ngay
        if ($request->boolean('assign_to_admin', true)) {
            $adminRole = Role::where('slug', 'admin')->first();
            if ($adminRole) {
                $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }

        Role::bumpPermissionsVersion();
        Permission::clearActiveFeaturesCache();

        return response()->json([
            'status' => true,
            'message' => 'Tạo quyền hạn mới "' . $permission->name . '" thành công!',
            'permission' => $permission,
        ]);
    }

    /**
     * Return permission data for editing via AJAX.
     */
    public function edit(Permission $permission): JsonResponse
    {
        return response()->json([
            'status' => true,
            'permission' => $permission,
        ]);
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'module' => ['required', 'string', 'max:50'],
            'module_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        if (!$permission->is_system) {
            $rules['slug'] = [
                'required', 'string', 'max:100',
                Rule::unique('permissions', 'slug')->ignore($permission->id),
                'regex:/^[a-z0-9_.-]+$/'
            ];
        }

        $validated = $request->validate($rules, [
            'name.required' => 'Vui lòng nhập tên quyền hạn.',
            'slug.required' => 'Mã quyền định danh (slug) không được để trống.',
            'slug.unique' => 'Mã quyền định danh này đã tồn tại trong hệ thống.',
            'slug.regex' => 'Mã quyền chỉ được chứa chữ thường, số, dấu chấm, gạch ngang và gạch dưới.',
            'module.required' => 'Vui lòng chọn hoặc nhập mã phân hệ.',
            'module_name.required' => 'Vui lòng nhập tên phân hệ hiển thị.',
        ]);

        $updateData = [
            'name' => trim($validated['name']),
            'module' => strtolower(trim($validated['module'])),
            'module_name' => trim($validated['module_name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ];

        if (!$permission->is_system && isset($validated['slug'])) {
            $updateData['slug'] = strtolower(trim($validated['slug']));
        }

        if ($request->has('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $updateData['status'] = $request->input('status');
        }

        $permission->update($updateData);

        \Illuminate\Support\Facades\Cache::forget('perm_active_' . $permission->slug);
        Permission::clearActiveFeaturesCache();
        \App\Models\Role::bumpPermissionsVersion();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật quyền hạn "' . $permission->name . '" thành công!',
            'permission' => $permission,
        ]);
    }

    /**
     * Toggle active/inactive status for a feature/permission.
     */
    public function toggleStatus(Permission $permission): JsonResponse
    {
        $newStatus = ($permission->status === 'active') ? 'inactive' : 'active';
        $permission->update(['status' => $newStatus]);

        \Illuminate\Support\Facades\Cache::forget('perm_active_' . $permission->slug);
        Permission::clearActiveFeaturesCache();
        \App\Models\Role::bumpPermissionsVersion();

        $actionLabel = ($newStatus === 'active') ? 'Bật tính năng / quyền hạn' : 'Tắt tính năng / quyền hạn';

        return response()->json([
            'status' => true,
            'message' => $actionLabel . ' "' . $permission->name . '" thành công!',
            'new_status' => $newStatus,
            'permission' => $permission,
        ]);
    }

    /**
     * Remove the specified custom permission from storage.
     */
    public function destroy(Permission $permission): JsonResponse
    {
        if ($permission->is_system) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xóa quyền hạn mặc định của hệ thống (' . $permission->name . ').',
            ], 422);
        }

        $name = $permission->name;
        $slug = $permission->slug;

        // Gỡ quan hệ với tất cả vai trò trước khi xóa
        $permission->roles()->detach();
        $permission->delete();

        \Illuminate\Support\Facades\Cache::forget('perm_active_' . $slug);
        Permission::clearActiveFeaturesCache();
        \App\Models\Role::bumpPermissionsVersion();

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa quyền hạn "' . $name . '" thành công!',
        ]);
    }
}
