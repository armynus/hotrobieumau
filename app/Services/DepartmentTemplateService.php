<?php

namespace App\Services;

use App\Models\Branches;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class DepartmentTemplateService
{
    /**
     * @return array{created: int, updated: int, unchanged: int}
     */
    public function sync(Branches $branch): array
    {
        $definitions = config('organization.department_templates.'.$branch->branch_type, []);
        $result = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

        DB::transaction(function () use ($branch, $definitions, &$result): void {
            foreach ($definitions as $definition) {
                $department = Department::query()
                    ->where('branch_id', $branch->id)
                    ->where(function ($query) use ($definition): void {
                        $query->where('department_code', $definition['code'])
                            ->orWhere('department_name', $definition['name']);
                    })
                    ->first();

                if (! $department) {
                    Department::create([
                        'branch_id' => $branch->id,
                        'department_code' => $definition['code'],
                        'department_name' => $definition['name'],
                        'status' => 'active',
                    ]);
                    $result['created']++;

                    continue;
                }

                $department->fill([
                    'department_code' => $definition['code'],
                    'department_name' => $definition['name'],
                    'status' => 'active',
                ]);

                if ($department->isDirty()) {
                    $department->save();
                    $result['updated']++;
                } else {
                    $result['unchanged']++;
                }
            }
        });

        return $result;
    }
}
