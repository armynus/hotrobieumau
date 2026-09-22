<?php

namespace App\Console\Commands;

use App\Models\Branches;
use App\Services\DepartmentTemplateService;
use Illuminate\Console\Command;

class SeedTypeTwoDepartments extends Command
{
    protected $signature = 'organization:seed-type2-departments
        {--branch=* : ID chi nhánh loại II cần nhập; bỏ trống để áp dụng tất cả}
        {--dry-run : Chỉ hiển thị chi nhánh và phòng ban sẽ được nhập}';

    protected $description = 'Nhập bộ phòng ban mặc định cho chi nhánh loại II, có thể chạy lại mà không tạo trùng';

    public function handle(DepartmentTemplateService $templates): int
    {
        $requestedBranchIds = collect($this->option('branch'));
        if ($requestedBranchIds->contains(fn ($id) => ! ctype_digit((string) $id))) {
            $this->error('Mỗi giá trị --branch phải là ID số nguyên của chi nhánh.');

            return self::INVALID;
        }

        $branchIds = $requestedBranchIds
            ->map(fn ($id) => (int) $id)
            ->values();

        $branches = Branches::query()
            ->where('branch_type', 'type_2')
            ->when($branchIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $branchIds))
            ->orderBy('id')
            ->get();

        if ($branches->isEmpty()) {
            $this->warn('Không tìm thấy chi nhánh loại II phù hợp.');

            return self::SUCCESS;
        }

        $definitions = config('organization.department_templates.type_2', []);
        if ($this->option('dry-run')) {
            $this->info('Sẽ nhập '.count($definitions).' phòng ban cho '.$branches->count().' chi nhánh loại II:');
            foreach ($branches as $branch) {
                $this->line("- [{$branch->id}] {$branch->branch_name}");
            }
            foreach ($definitions as $definition) {
                $this->line("  • {$definition['code']} — {$definition['name']}");
            }

            return self::SUCCESS;
        }

        $totals = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
        foreach ($branches as $branch) {
            $result = $templates->sync($branch);
            foreach ($totals as $key => $value) {
                $totals[$key] += $result[$key];
            }
            $this->line("[{$branch->id}] {$branch->branch_name}: thêm {$result['created']}, cập nhật {$result['updated']}, giữ nguyên {$result['unchanged']}.");
        }

        $this->info("Hoàn tất: thêm {$totals['created']}, cập nhật {$totals['updated']}, giữ nguyên {$totals['unchanged']}.");

        return self::SUCCESS;
    }
}
