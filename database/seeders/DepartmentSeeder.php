<?php

namespace Database\Seeders;

use App\Models\Branches;
use App\Services\DepartmentTemplateService;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(DepartmentTemplateService $templates): void
    {
        Branches::query()
            ->whereIn('branch_type', array_keys(config('organization.department_templates', [])))
            ->orderBy('id')
            ->each(fn (Branches $branch) => $templates->sync($branch));
    }
}
