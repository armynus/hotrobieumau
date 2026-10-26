<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branches;
use App\Services\DepartmentTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    public function create()
    {
        return view('admin.branches.create');
    }

    public function store(Request $request, DepartmentTemplateService $departmentTemplates)
    {
        $validated = $this->validateBranch($request);
        $validated['parent_id'] = $validated['branch_type'] === 'type_2' ? $validated['parent_id'] : null;
        $validated['status'] = 'active';

        $branchId = (int) $branch->id;
        if ($branchId <= 0) {
            throw new \RuntimeException('Mã chi nhánh không hợp lệ để tạo database.');
        }
        $databaseName = 'branch_'.$branchId;
        $branch->update(['database_name' => $databaseName]);

        $databaseCreated = false;
        try {
            DB::statement("CREATE DATABASE `{$databaseName}`");
            $databaseCreated = true;
            config(['database.connections.tenant.database' => $databaseName]);
            DB::purge('tenant');
            Artisan::call('migrate', [
                '--path' => 'database/migrations/branch',
                '--database' => 'tenant',
                '--force' => true,
            ]);

            $departmentTemplates->sync($branch->refresh());
        } catch (\Throwable $exception) {
            report($exception);
            if ($databaseCreated) {
                DB::statement("DROP DATABASE `{$databaseName}`");
            }
            $branch->delete();

            return response()->json([
                'message' => 'Không thể khởi tạo cơ sở dữ liệu cho chi nhánh. Không có dữ liệu dở dang được giữ lại.',
                'status' => false,
            ], 500);
        }

        \App\Services\ActivityLogService::logBusiness(
            'branches',
            'branch.create',
            'Branch',
            (string) $branch->id,
            $branch->branch_name,
            "Thêm mới chi nhánh: {$branch->branch_name} ({$branch->branch_code})",
            ['branch_id' => $branch->id, 'branch_code' => $branch->branch_code]
        );

        return response()->json([
            'message' => 'Chi nhánh và cơ sở dữ liệu đã được thiết lập.',
            'branch' => $branch->fresh('parent'),
            'status' => true,
        ]);
    }

    public function edit(Request $request)
    {
        $branch = Branches::with('parent')->findOrFail($request->integer('branch_id'));

        return response()->json([
            'branch' => $branch,
            'status' => true,
        ]);
    }

    public function update(Request $request, DepartmentTemplateService $departmentTemplates)
    {
        $branch = Branches::findOrFail($request->integer('branch_id'));
        $validated = $this->validateBranch($request, $branch);
        if ($validated['branch_type'] !== 'type_1' && $branch->children()->exists()) {
            throw ValidationException::withMessages([
                'branch_type' => 'Chi nhánh này đang quản lý đơn vị loại II nên phải giữ loại I.',
            ]);
        }
        $validated['parent_id'] = $validated['branch_type'] === 'type_2' ? $validated['parent_id'] : null;
        $branch->update($validated);
        $departmentTemplates->sync($branch->refresh());

        \App\Services\ActivityLogService::logBusiness(
            'branches',
            'branch.update',
            'Branch',
            (string) $branch->id,
            $branch->branch_name,
            "Cập nhật thông tin chi nhánh: {$branch->branch_name}",
            ['branch_id' => $branch->id, 'branch_code' => $branch->branch_code]
        );

        return response()->json([
            'message' => 'Cập nhật chi nhánh thành công.',
            'status' => true,
            'branch' => $branch->fresh('parent'),
        ]);
    }

    public function lock(Request $request)
    {
        $branch = Branches::findOrFail($request->integer('branch_id'));
        $newStatus = $branch->status === 'active' ? 'inactive' : 'active';
        $branch->update(['status' => $newStatus]);

        \App\Services\ActivityLogService::logBusiness(
            'branches',
            $newStatus === 'active' ? 'branch.unlock' : 'branch.lock',
            'Branch',
            (string) $branch->id,
            $branch->branch_name,
            ($newStatus === 'active' ? 'Mở khóa' : 'Khóa') . " chi nhánh: {$branch->branch_name}",
            ['branch_id' => $branch->id, 'status' => $newStatus]
        );

        return response()->json([
            'message' => $branch->status === 'active' ? 'Mở khóa chi nhánh thành công.' : 'Khóa chi nhánh thành công.',
            'status' => true,
            'branch_status' => $branch->status,
        ]);
    }

    private function validateBranch(Request $request, ?Branches $branch = null): array
    {
        $type = $request->string('branch_type')->toString();

        return $request->validate([
            'branch_name' => ['required', 'string', 'max:255', Rule::unique('branches', 'branch_name')->ignore($branch?->id)],
            'branch_code' => ['nullable', 'string', 'max:11', Rule::unique('branches', 'branch_code')->ignore($branch?->id)],
            'branch_type' => ['required', Rule::in(['central', 'type_1', 'type_2'])],
            'parent_id' => [
                Rule::requiredIf($type === 'type_2'),
                'nullable',
                'integer',
                Rule::notIn(array_filter([$branch?->id])),
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('branch_type', 'type_1')->where('status', 'active')),
            ],
            'branch_addr' => ['nullable', 'string', 'max:255'],
            'branch_phone' => ['nullable', 'string', 'max:30'],
            'branch_fax' => ['nullable', 'string', 'max:30'],
            'branch_place' => ['nullable', 'string', 'max:255'],
            'branch_tax_code' => ['nullable', 'string', 'max:50'],
            'branch_tax_date' => ['nullable', 'date_format:Y-m-d'],
            'branch_tax_place' => ['nullable', 'string', 'max:255'],
            'branch_general' => ['nullable', 'string', 'max:255'],
        ], [
            'branch_code.unique' => 'Mã chi nhánh đã được sử dụng.',
            'parent_id.required' => 'Chi nhánh loại II phải chọn chi nhánh loại I quản lý.',
            'parent_id.exists' => 'Đơn vị quản lý phải là chi nhánh loại I.',
        ]);
    }
}
