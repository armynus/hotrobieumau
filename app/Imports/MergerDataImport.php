<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MergerDataImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $oldProvinceName = trim($row['tinh_cu'] ?? '');
            $oldDistrictName = trim($row['huyen_cu'] ?? '');
            $oldWardName = trim($row['xa_cu'] ?? '');
            $newProvinceName = trim($row['tinh_moi'] ?? '');
            $newWardName = trim($row['xa_moi'] ?? '');

            if (!$oldProvinceName || !$oldDistrictName || !$oldWardName || !$newProvinceName || !$newWardName) {
                continue;
            }

            // Insert or get Old Province
            $oldProvince = DB::table('old_provinces')->where('name', $oldProvinceName)->first();
            $oldProvinceId = $oldProvince ? $oldProvince->id : DB::table('old_provinces')->insertGetId(['name' => $oldProvinceName]);

            // Insert or get Old District
            $oldDistrict = DB::table('old_districts')->where('name', $oldDistrictName)->where('old_province_id', $oldProvinceId)->first();
            $oldDistrictId = $oldDistrict ? $oldDistrict->id : DB::table('old_districts')->insertGetId([
                'name' => $oldDistrictName,
                'old_province_id' => $oldProvinceId
            ]);

            // Insert or get Old Ward
            $oldWard = DB::table('old_wards')->where('name', $oldWardName)->where('old_district_id', $oldDistrictId)->first();
            $oldWardId = $oldWard ? $oldWard->id : DB::table('old_wards')->insertGetId([
                'name' => $oldWardName,
                'old_district_id' => $oldDistrictId
            ]);

            // Insert or get New Province
            $newProvince = DB::table('new_provinces')->where('name', $newProvinceName)->first();
            $newProvinceId = $newProvince ? $newProvince->id : DB::table('new_provinces')->insertGetId(['name' => $newProvinceName]);

            // Insert or get New Ward
            $newWard = DB::table('new_wards')->where('name', $newWardName)->where('new_province_id', $newProvinceId)->first();
            $newWardId = $newWard ? $newWard->id : DB::table('new_wards')->insertGetId([
                'name' => $newWardName,
                'new_province_id' => $newProvinceId
            ]);

            // Mapping Province
            DB::table('province_mappings')->updateOrInsert(
                ['old_province_id' => $oldProvinceId, 'new_province_id' => $newProvinceId]
            );

            // Mapping Ward
            DB::table('ward_mappings')->updateOrInsert(
                ['old_ward_id' => $oldWardId, 'new_ward_id' => $newWardId]
            );
        }
    }
}
