<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Department::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        $departments = [
            ['department_name' => 'Phòng Tổng hợp', 'branch_id' => 1, 'status' => 'active'],
            ['department_name' => 'Phòng Kiểm tra giám sát nội bộ', 'branch_id' => 1, 'status' => 'active'],
            ['department_name' => 'Phòng Kế toán & Ngân Quỹ', 'branch_id' => 1, 'status' => 'active'],
            ['department_name' => 'Phòng Khách hàng Doanh nghiệp', 'branch_id' => 1, 'status' => 'active'],
            ['department_name' => 'Phòng Khách hàng Cá nhân', 'branch_id' => 1, 'status' => 'active'],
            ['department_name' => 'Phòng Thẩm định', 'branch_id' => 1, 'status' => 'active'],
            ['department_name' => 'Phòng Kế hoạch & Quản lý rủi ro', 'branch_id' => 1, 'status' => 'active'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['department_name' => $dept['department_name'], 'branch_id' => $dept['branch_id']],
                ['status' => $dept['status']]
            );
        }
    }
}
