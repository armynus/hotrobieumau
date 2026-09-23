<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branches;
use App\Models\Department;
use App\Models\Position;
use App\Models\TransactionOffice;
use App\Models\Users;
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

        return view('admin.users.list_user', compact('list_branch', 'list_department', 'list_position', 'list_transaction_office'));
    }

    public function data(Request $request)
    {
        $users = Users::query()
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->leftJoin('transaction_offices', 'users.transaction_office_id', '=', 'transaction_offices.id')
            ->leftJoin('positions', 'users.position_id', '=', 'positions.id')
            ->select([
                'users.id', 'users.name', 'users.email', 'users.user_ipcas', 'users.role_id',
                'users.document_role', 'users.status', 'users.created_at', 'users.updated_at',
                'branches.branch_name', 'departments.department_name', 'transaction_offices.office_name as transaction_office_name', 'positions.position_name',
            ])
            ->whereNotNull('users.role_id');

        return DataTables::eloquent($users)
            ->filterColumn('name', fn ($query, $keyword) => $query->where(function ($nested) use ($keyword): void {
                $nested->where('users.name', 'like', '%'.$keyword.'%')
                    ->orWhere('users.user_ipcas', 'like', '%'.$keyword.'%');
            }))
            ->filterColumn('branch_name', fn ($query, $keyword) => $query->where('branches.branch_name', 'like', '%'.$keyword.'%'))
            ->filterColumn('department_name', fn ($query, $keyword) => $query->where('departments.department_name', 'like', '%'.$keyword.'%'))
            ->filterColumn('transaction_office_name', fn ($query, $keyword) => $query->where('transaction_offices.office_name', 'like', '%'.$keyword.'%'))
            ->filterColumn('position_name', fn ($query, $keyword) => $query->where('positions.position_name', 'like', '%'.$keyword.'%'))
            ->toJson();
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request);
        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';
        $validated['user_ipcas'] = $validated['user_ipcas'] ?? null;
        $validated['department_id'] = $validated['department_id'] ?? null;
        $validated['transaction_office_id'] = $validated['transaction_office_id'] ?? null;
        $validated['position_id'] = $validated['position_id'] ?? null;

        $user = Users::create($validated);

        return response()->json([
            'success' => 'Thêm tài khoản thành công!',
            'status' => true,
            'user' => $user,
        ]);
    }

    public function edit(Request $request)
    {
        $user = Users::query()
            ->where(function ($q) {
                $q->where('role_id', '!=', 0)
                  ->orWhere('id', Session::get('admin_id'));
            })
            ->select('id', 'name', 'email', 'user_ipcas', 'branch_id', 'role_id', 'department_id', 'transaction_office_id', 'position_id', 'document_role')
            ->findOrFail($request->integer('user_id'));

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $user = Users::query()
            ->where(function ($q) {
                $q->where('role_id', '!=', 0)
                  ->orWhere('id', Session::get('admin_id'));
            })
            ->findOrFail($request->integer('user_id'));
        $validated = $this->validateUser($request, $user);
        $password = $validated['password'] ?? null;
        unset($validated['password']);
        $validated['user_ipcas'] = $validated['user_ipcas'] ?? null;
        $validated['department_id'] = $validated['department_id'] ?? null;
        $validated['transaction_office_id'] = $validated['transaction_office_id'] ?? null;
        $validated['position_id'] = $validated['position_id'] ?? null;
        $user->fill($validated);

        if ($password) {
            $user->password = Hash::make($password);
        }

        $user->save();

        return response()->json([
            'message' => 'Cập nhật tài khoản thành công!',
            'status' => true,
            'user' => $user,
        ]);
    }

    public function lock(Request $request)
    {
        $user = Users::where('id', $request->user_id)->first();
        if (! $user) {
            return response()->json([
                'message' => 'Tài khoản không tồn tại.',
                'status' => false,
            ]);
        }
        if ($user->status == 'active') {
            Users::where('id', $request->user_id)->update(['status' => 'inactive']);

            return response()->json([
                'message' => 'Khóa tài khoản thành công.',
                'status' => true,
            ]);
        } else {
            Users::where('id', $request->user_id)->update(['status' => 'active', 'failed_login_attempts' => '0']);

            return response()->json([
                'message' => 'Mở khóa tài khoản thành công.',
                'status' => true,
            ]);
        }
    }

    private function validateUser(Request $request, ?Users $user = null): array
    {
        $branchId = $request->integer('branch_id');
        $allowedRoles = $user && $user->role_id == 0 ? ['0', '1', '2', 0, 1, 2] : ['1', '2', 1, 2];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'user_ipcas' => ['nullable', 'string', 'max:50', Rule::unique('users', 'user_ipcas')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'max:255'],
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
            'role_id' => ['required', Rule::in($allowedRoles)],
            'document_role' => ['required', Rule::in(['user', 'clerk'])],
        ], [
            'department_id.exists' => 'Phòng ban không thuộc chi nhánh đã chọn.',
            'transaction_office_id.exists' => 'Phòng giao dịch không hoạt động hoặc không thuộc chi nhánh đã chọn.',
            'email.unique' => 'Email đã được sử dụng.',
            'user_ipcas.unique' => 'Mã người dùng IPCAS đã được sử dụng.',
        ]);
    }
}
