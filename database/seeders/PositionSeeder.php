<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Position::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        $positions = [
            ['position_name' => 'Giám đốc', 'level' => 1, 'status' => 'active'],
            ['position_name' => 'Phó giám đốc', 'level' => 2, 'status' => 'active'],
            ['position_name' => 'Trưởng phòng', 'level' => 3, 'status' => 'active'],
            ['position_name' => 'Phó phòng', 'level' => 4, 'status' => 'active'],
            ['position_name' => 'Giám đốc phòng giao dịch', 'level' => 3, 'status' => 'active'],
            ['position_name' => 'Phó giám đốc phòng giao dịch', 'level' => 4, 'status' => 'active'],
            ['position_name' => 'Tổ trưởng phòng giao dịch', 'level' => 5, 'status' => 'active'],
            ['position_name' => 'Nhân viên', 'level' => 6, 'status' => 'active'],
        ];

        foreach ($positions as $pos) {
            // Use updateOrCreate to avoid duplicates
            Position::updateOrCreate(
                ['position_name' => $pos['position_name']],
                ['level' => $pos['level'], 'status' => $pos['status']]
            );
        }
    }
}
