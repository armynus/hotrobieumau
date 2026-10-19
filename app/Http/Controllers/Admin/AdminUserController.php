<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\Branches;
use App\Models\Department;
use App\Models\Position;
use App\Models\TransactionOffice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class AdminUserController extends Controller
{
    public function index()
    {
        $list_branch = Branches::query()
            ->select('id', 'branch_name', 'branch_type')
            ->where('status', 'active')
            ->orderBy('branch_name')
            ->get();
        $list_department = Department::query()
            ->select('id', 'branch_id', 'department_name', 'department_code')
            ->where('status', 'active')
            ->orderBy('department_name')
            ->get();
        $list_position = Position::query()
            ->select('id', 'position_name', 'position_code')
            ->where('status', 'active')
            ->orderBy('level')
            ->orderBy('position_name')
            ->get();
        $list_transaction_office = TransactionOffice::query()
            ->select('id', 'branch_id', 'office_name', 'office_code', 'status')
            ->orderBy('office_name')
            ->get();
        $list_roles = \Illuminate\Support\Facades\Schema::hasTable('roles')
            ? \App\Models\Role::query()->where('status', 'active')->orderBy('id')->get()
            : collect();

        return view('admin.users.list_user', compact('list_branch', 'list_department', 'list_position', 'list_transaction_office', 'list_roles'));
    }

    public function data(Request $request)
    {
        $hasRolesTable = \Illuminate\Support\Facades\Schema::hasTable('roles');
        $users = User::query();

        if ($hasRolesTable) {
            $users->with(['roles' => fn($q) => $q->select('roles.id', 'roles.name', 'roles.slug', 'roles.is_system')]);
        }

        $users->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->leftJoin('transaction_offices', 'users.transaction_office_id', '=', 'transaction_offices.id')
            ->leftJoin('positions', 'users.position_id', '=', 'positions.id')
            ->select([
                'users.id', 'users.name', 'users.citizen_id', 'users.employee_code',
                'users.email', 'users.phone_no', 'users.user_ipcas', 'users.role_id',
                'users.document_role', 'users.status', 'users.failed_login_attempts', 'users.created_at', 'users.updated_at',
                'branches.branch_name', 'departments.department_name', 'transaction_offices.office_name as transaction_office_name', 'positions.position_name',
            ])
            ->whereNotNull('users.role_id');

        if ($request->filled('branch_id')) {
            $users->where('users.branch_id', $request->input('branch_id'));
        }

        if ($request->filled('department_id')) {
            $users->where('users.department_id', $request->input('department_id'));
        }

        if ($request->filled('transaction_office_id')) {
            $users->where('users.transaction_office_id', $request->input('transaction_office_id'));
        }

        if ($request->filled('position_id')) {
            $users->where('users.position_id', $request->input('position_id'));
        }

        if ($request->filled('role_id')) {
            $users->where('users.role_id', $request->input('role_id'));
        }

        if ($request->filled('document_role')) {
            $docRole = $request->input('document_role');
            if ($docRole === 'clerk') {
                $users->where('users.document_role', 'clerk');
            } elseif ($docRole === 'user') {
                $users->where(function ($q) {
                    $q->whereNull('users.document_role')->orWhere('users.document_role', '!=', 'clerk');
                });
            }
        }

        if ($request->filled('status')) {
            $users->where('users.status', $request->input('status'));
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            if ($keyword !== '') {
                $users->where(function ($q) use ($keyword) {
                    $q->where('users.name', 'like', '%'.$keyword.'%')
                        ->orWhere('users.email', 'like', '%'.$keyword.'%')
                        ->orWhere('users.phone_no', 'like', '%'.$keyword.'%')
                        ->orWhere('users.user_ipcas', 'like', '%'.$keyword.'%')
                        ->orWhere('users.citizen_id', 'like', '%'.$keyword.'%')
                        ->orWhere('users.employee_code', 'like', '%'.$keyword.'%');
                });
            }
        }

        $stats = User::whereNotNull('role_id')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status != 'active' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN document_role = 'clerk' THEN 1 ELSE 0 END) as clerk,
                SUM(CASE WHEN role_id = 0 THEN 1 ELSE 0 END) as admin
            ")
            ->first();

        $dataTable = DataTables::eloquent($users);

        return $dataTable
            ->filterColumn('name', fn ($query, $keyword) => $query->where(function ($nested) use ($keyword): void {
                $nested->where('users.name', 'like', '%'.$keyword.'%')
                    ->orWhere('users.user_ipcas', 'like', '%'.$keyword.'%')
                    ->orWhere('users.citizen_id', 'like', '%'.$keyword.'%')
                    ->orWhere('users.employee_code', 'like', '%'.$keyword.'%');
            }))
            ->filterColumn('citizen_id', fn ($query, $keyword) => $query->where('users.citizen_id', 'like', '%'.$keyword.'%'))
            ->filterColumn('employee_code', fn ($query, $keyword) => $query->where('users.employee_code', 'like', '%'.$keyword.'%'))
            ->filterColumn('branch_name', fn ($query, $keyword) => $query->where('branches.branch_name', 'like', '%'.$keyword.'%'))
            ->filterColumn('department_name', fn ($query, $keyword) => $query->where('departments.department_name', 'like', '%'.$keyword.'%'))
            ->filterColumn('transaction_office_name', fn ($query, $keyword) => $query->where('transaction_offices.office_name', 'like', '%'.$keyword.'%'))
            ->filterColumn('position_name', fn ($query, $keyword) => $query->where('positions.position_name', 'like', '%'.$keyword.'%'))
            ->with('stats', [
                'total' => (int) ($stats->total ?? 0),
                'active' => (int) ($stats->active ?? 0),
                'inactive' => (int) ($stats->inactive ?? 0),
                'clerk' => (int) ($stats->clerk ?? 0),
                'admin' => (int) ($stats->admin ?? 0),
            ])
            ->toJson();
    }

    public function store(Request $request)
    {
        $validated = $this->resolveFormRequest($request, StoreAdminUserRequest::class)->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';
        $validated['citizen_id'] = $validated['citizen_id'] ?? null;
        $validated['employee_code'] = $validated['employee_code'] ?? null;
        $validated['phone_no'] = !empty($validated['phone_no']) ? trim($validated['phone_no']) : null;
        $validated['user_ipcas'] = $validated['user_ipcas'] ?? null;
        $validated['department_id'] = $validated['department_id'] ?? null;
        $validated['transaction_office_id'] = $validated['transaction_office_id'] ?? null;
        $validated['position_id'] = $validated['position_id'] ?? null;

        $user = User::create($validated);
        $this->syncUserRoles($user, $request);
        \App\Models\User::bumpUserPermissionsVersion($user->id);
        \App\Models\Role::bumpPermissionsVersion();

        if (\Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $user->load('roles');
        }

        return response()->json([
            'success' => 'Thêm tài khoản thành công!',
            'status' => true,
            'user' => $user,
        ]);
    }

    public function edit(Request $request)
    {
        $user = User::query()
            ->where(function ($q) {
                $q->where('role_id', '!=', 0)
                  ->orWhere('id', Session::get('admin_id'));
            })
            ->select([
                'id', 'name', 'citizen_id', 'employee_code', 'email', 'phone_no', 'user_ipcas',
                'branch_id', 'role_id', 'department_id', 'transaction_office_id',
                'position_id', 'document_role', 'status', 'failed_login_attempts', 'created_at', 'updated_at',
            ])
            ->findOrFail($request->integer('user_id'));

        $roleIds = \Illuminate\Support\Facades\Schema::hasTable('user_role')
            ? $user->roles()->pluck('roles.id')->toArray()
            : [];

        // Fallback for legacy user without user_role records
        if (empty($roleIds) && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $slugs = [];
            if ((int) $user->role_id === 0) {
                $slugs[] = 'admin';
            } elseif ((int) $user->role_id === 1) {
                $slugs[] = 'supervisor';
            } else {
                $slugs[] = 'staff';
            }
            if (($user->document_role ?? '') === 'clerk') {
                $slugs[] = 'clerk';
            }
            $roleIds = \App\Models\Role::whereIn('slug', array_unique($slugs))->pluck('id')->toArray();
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $user->load('roles');
        }

        return response()->json([
            'user' => $user,
            'role_ids' => $roleIds,
        ]);
    }

    public function update(Request $request)
    {
        $user = User::query()
            ->where(function ($q) {
                $q->where('role_id', '!=', 0)
                  ->orWhere('id', Session::get('admin_id'));
            })
            ->findOrFail($request->integer('user_id'));

        $validated = $this->resolveFormRequest($request, UpdateAdminUserRequest::class)->validated();
        $password = $validated['password'] ?? null;
        unset($validated['password']);
        $validated['citizen_id'] = $validated['citizen_id'] ?? null;
        $validated['employee_code'] = $validated['employee_code'] ?? null;
        $validated['phone_no'] = !empty($validated['phone_no']) ? trim($validated['phone_no']) : null;
        $validated['user_ipcas'] = $validated['user_ipcas'] ?? null;
        $validated['department_id'] = $validated['department_id'] ?? null;
        $validated['transaction_office_id'] = $validated['transaction_office_id'] ?? null;
        $validated['position_id'] = $validated['position_id'] ?? null;
        $user->fill($validated);

        if ($password) {
            $user->password = Hash::make($password);
        }

        $user->save();
        $this->syncUserRoles($user, $request);
        \App\Models\User::bumpUserPermissionsVersion($user->id);
        \App\Models\Role::bumpPermissionsVersion();

        $userFresh = $user->fresh();
        if (\Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $userFresh->load('roles');
        }

        return response()->json([
            'message' => 'Cập nhật tài khoản thành công!',
            'status' => true,
            'user' => $userFresh,
        ]);
    }

    public function lock(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();
        if (! $user) {
            return response()->json([
                'message' => 'Tài khoản không tồn tại.',
                'status' => false,
            ]);
        }
        if ($user->status == 'active') {
            User::where('id', $request->user_id)->update(['status' => 'inactive']);
            \App\Models\User::bumpUserPermissionsVersion((int) $request->user_id);
            \App\Models\Role::bumpPermissionsVersion();

            return response()->json([
                'message' => 'Khóa tài khoản thành công.',
                'status' => true,
            ]);
        } else {
            User::where('id', $request->user_id)->update(['status' => 'active', 'failed_login_attempts' => '0']);
            \App\Models\User::bumpUserPermissionsVersion((int) $request->user_id);
            \App\Models\Role::bumpPermissionsVersion();

            return response()->json([
                'message' => 'Mở khóa tài khoản thành công.',
                'status' => true,
            ]);
        }
    }

    private function syncUserRoles(User $user, Request $request): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('roles') || !\Illuminate\Support\Facades\Schema::hasTable('user_role')) {
            return;
        }

        if ($request->has('role_ids')) {
            $roleIds = array_filter((array) $request->input('role_ids'));
            if (!empty($roleIds)) {
                $user->roles()->sync($roleIds);
                $roles = \App\Models\Role::whereIn('id', $roleIds)->pluck('slug')->toArray();
                if (in_array('admin', $roles, true)) {
                    $user->role_id = 0;
                } elseif (in_array('supervisor', $roles, true)) {
                    $user->role_id = 1;
                } else {
                    $user->role_id = 2;
                }
                if (in_array('clerk', $roles, true)) {
                    $user->document_role = 'clerk';
                } elseif ($request->has('document_role')) {
                    $user->document_role = $request->input('document_role');
                } else {
                    $user->document_role = 'user';
                }
                $user->save();
                return;
            }
        }

        // Fallback sync for legacy form inputs (role_id, document_role)
        $slugsToSync = [];
        if ((int) $user->role_id === 0) {
            $slugsToSync[] = 'admin';
        } elseif ((int) $user->role_id === 1) {
            $slugsToSync[] = 'supervisor';
        } else {
            $slugsToSync[] = 'staff';
        }

        if (($user->document_role ?? '') === 'clerk') {
            $slugsToSync[] = 'clerk';
        }

        $roleIds = \App\Models\Role::whereIn('slug', array_unique($slugsToSync))->pluck('id')->toArray();
        if (!empty($roleIds)) {
            $user->roles()->sync($roleIds);
        }
    }
}
