<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AdminRoleController extends Controller
{
    /**
     * Display a listing of the roles with their statistics and permissions matrix.
     */
    public function index()
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->with(['permissions' => function ($q) {
                $q->select('permissions.id', 'permissions.name', 'permissions.slug', 'permissions.module');
            }])
            ->orderBy('id')
            ->get();

        $permissions = Permission::orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groupedPermissions = $permissions->groupBy('module_name');

        $stats = [
            'total_roles' => $roles->count(),
            'system_roles' => $roles->where('is_system', true)->count(),
            'total_permissions' => $permissions->count(),
            'assigned_users' => DB::table('user_role')->distinct('user_id')->count('user_id'),
        ];

        $modules = Permission::select('module', 'module_name')
            ->distinct()
            ->orderBy('module_name')
            ->get();

        return view('admin.roles.index', compact('roles', 'permissions', 'groupedPermissions', 'modules', 'stats'));
    }

    /**
     * Store a newly created custom role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'slug' => ['nullable', 'string', 'max:50', 'unique:roles,slug', 'regex:/^[a-z0-9_-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ], [
            'name.required' => 'Vui lòng nhập tên vai trò.',
            'name.unique' => 'Tên vai trò này đã tồn tại.',
            'slug.unique' => 'Mã vai trò (slug) đã tồn tại.',
            'slug.regex' => 'Mã vai trò chỉ được chứa chữ thường, số, gạch ngang và gạch dưới.',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug'], '_') : Str::slug($validated['name'], '_');

        // Check if generated slug exists
        if (Role::where('slug', $slug)->exists()) {
            $slug .= '_' . time();
        }

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_system' => false,
            'status' => 'active',
        ]);

        if (!empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }
        Role::bumpPermissionsVersion();

        return response()->json([
            'status' => true,
            'message' => 'Thêm mới vai trò ' . $role->name . ' thành công!',
            'role' => $role->loadCount(['permissions', 'users']),
        ]);
    }

    /**
     * Show the specified role with its permissions for editing.
     */
    public function edit(Role $role)
    {
        $role->load(['permissions' => function ($q) {
            $q->select('permissions.id', 'permissions.name', 'permissions.slug', 'permissions.module');
        }]);

        return response()->json([
            'status' => true,
            'role' => $role,
            'permission_ids' => $role->permissions->pluck('id')->toArray(),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $rules = [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];

        if (!$role->is_system) {
            $rules['slug'] = ['required', 'string', 'max:50', Rule::unique('roles', 'slug')->ignore($role->id), 'regex:/^[a-z0-9_-]+$/'];
        }

        $validated = $request->validate($rules, [
            'name.required' => 'Vui lòng nhập tên vai trò.',
            'name.unique' => 'Tên vai trò này đã tồn tại.',
            'slug.required' => 'Mã vai trò không được để trống.',
            'slug.unique' => 'Mã vai trò đã tồn tại.',
            'slug.regex' => 'Mã vai trò chỉ được chứa chữ thường, số, gạch ngang và gạch dưới.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ];

        if (!$role->is_system && isset($validated['slug'])) {
            $updateData['slug'] = Str::slug($validated['slug'], '_');
        }

        $role->update($updateData);

        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->input('permission_ids', []));
        }
        Role::bumpPermissionsVersion();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật vai trò ' . $role->name . ' thành công!',
            'role' => $role->fresh()->loadCount(['permissions', 'users']),
        ]);
    }

    /**
     * Update permissions matrix for a role directly.
     */
    public function updateMatrix(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = $validated['permission_ids'] ?? [];
        $role->permissions()->sync($permissionIds);
        Role::bumpPermissionsVersion();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật ma trận quyền hạn cho vai trò "' . $role->name . '" thành công (' . count($permissionIds) . ' quyền)!',
            'role_id' => $role->id,
            'permission_count' => count($permissionIds),
        ]);
    }

    /**
     * Remove the specified custom role.
     */
    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xóa vai trò mặc định của hệ thống (' . $role->name . ').',
            ], 422);
        }

        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xóa vai trò này vì đang có ' . $userCount . ' nhân viên được gán vai trò này. Vui lòng chuyển vai trò của nhân viên trước.',
            ], 422);
        }

        $name = $role->name;
        $role->permissions()->detach();
        $role->delete();
        Role::bumpPermissionsVersion();

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa vai trò "' . $name . '" thành công.',
        ]);
    }
}
