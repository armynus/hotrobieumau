<?php

namespace App\Services\Geography;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GeographyImportService
{
    public function __construct(private GeographyStore $store) {}

    public function importFile(string $path, string $name, ?int $actor = null): int
    {
        if (! is_file($path) || filesize($path) > 25 * 1024 * 1024) {
            $this->invalid('Không đọc được file hoặc file lớn hơn 25 MB.');
        }
        $raw = file_get_contents($path);
        $sha = hash('sha256', $raw);
        $current = $this->store->current();
        // Re-uploading the current source must not erase manual corrections.
        if ($current && $current->sha256 === $sha) {
            return (int) $current->id;
        }
        $trusted = in_array($sha, config('geography.reviewed_hashes', []), true);
        // Reuse the reconciled three-file result when the exact original mapping workbook is uploaded.
        $prepared = config('geography.prepared.national.path');
        if ($sha === config('geography.mapping_source_hash') && $prepared && is_file($prepared) && in_array(hash_file('sha256', $prepared), config('geography.reviewed_hashes', []), true)) {
            $preparedData = json_decode(file_get_contents($prepared), true);
            if (($preparedData['sources']['mapping']['sha256'] ?? '') === $sha) {
                [$records, $notes, $date] = $this->fromJson($preparedData, true);
                $extension = 'xlsx';
            }
        }
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        if (! isset($records) && str_starts_with(ltrim($json), '{')) {
            try {
                $data = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->invalid('File JSON không hợp lệ.');
            }
            if (! is_array($data)) {
                $this->invalid('Cấu trúc JSON không hợp lệ.');
            }
            [$records, $notes, $date] = $this->fromJson($data, $trusted);
            $extension = 'json';
        } elseif (! isset($records)) {
            [$records, $notes, $date, $extension] = $this->fromExcel($path);
        }
        $rows = $this->cleanRows($records, $notes);
        if (! count(array_filter($rows, fn ($row) => $row['enabled']))) {
            $this->invalid('Không có liên kết hợp lệ để nhập. Dữ liệu hiện tại được giữ nguyên.');
        }
        $source = 'geography/imports/'.Str::uuid().'.'.$extension;
        if (! Storage::disk('local')->put($source, $raw)) {
            $this->invalid('Không lưu được bản nguồn. Chưa thay đổi dữ liệu.');
        }
        try {
            return $this->store->db()->transaction(function () use ($rows, $notes, $date, $name, $sha, $source, $actor) {
                $id = $this->store->db()->table('geography_imports')->insertGetId([
                    'name' => mb_substr($name, 0, 255), 'sha256' => $sha, 'snapshot_date' => $date,
                    'source_path' => $source, 'notes' => json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'created_by' => $actor, 'created_at' => now(),
                ]);
                foreach (array_chunk($rows, 100) as $chunk) {
                    $this->store->db()->table('geography_records')->insert(array_map(fn ($row) => ['import_id' => $id] + $row, $chunk));
                }

                return $id;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($source);
            throw $e;
        }
    }

    private function fromJson(array $data, bool $trusted): array
    {
        Validator::make($data, [
            'schemaVersion' => 'required|integer|in:1', 'snapshotDate' => 'required|date_format:Y-m-d',
            'units' => 'required|array|min:1|max:10000', 'links' => 'required|array|min:1|max:30000',
            'rejected' => 'sometimes|array|max:30000', 'issues' => 'sometimes|array|max:30000',
        ])->validate();
        $units = [];
        foreach ($data['units'] as $unit) {
            if (! is_array($unit)) {
                $this->invalid('Danh mục đơn vị mới không hợp lệ.');
            }
            Validator::make($unit, ['code' => ['required', 'string', 'regex:/^\d{5}$/D'], 'name' => 'required|string|max:255', 'province' => 'required|string|max:255'])->validate();
            if (isset($units[$unit['code']])) {
                $this->invalid('Trùng mã đơn vị mới '.$unit['code']);
            }
            $units[$unit['code']] = $unit;
        }
        $records = [];
        $notes = [];
        foreach ($data['issues'] ?? [] as $issue) {
            if (! is_array($issue)) {
                $this->invalid('Ghi chú nguồn không hợp lệ.');
            }
            foreach (['source', 'row', 'context', 'message', 'original', 'proposed'] as $field) {
                if (isset($issue[$field]) && ! is_scalar($issue[$field])) {
                    $this->invalid('Ghi chú nguồn phải là văn bản.');
                }
            }
            $notes[] = ['source' => $issue['source'] ?? '', 'row' => $issue['row'] ?? '', 'location' => $issue['context'] ?? '',
                'message' => $issue['message'] ?? '', 'original' => $issue['original'] ?? '', 'corrected' => $issue['proposed'] ?? ''];
        }
        foreach (array_merge($data['links'], $data['rejected'] ?? []) as $link) {
            if (! is_array($link)) {
                $this->invalid('Liên kết không hợp lệ.');
            }
            Validator::make($link, ['newCode' => ['required', 'string', 'regex:/^\d{5}$/D'], 'evidence' => 'nullable|string|max:2000'])->validate();
            $unit = $units[$link['newCode'] ?? ''] ?? null;
            if (! $unit || ($link['newName'] ?? null) !== $unit['name'] || ($link['newProvince'] ?? null) !== $unit['province']) {
                $this->invalid('Liên kết không khớp danh mục mã/tên mới.');
            }
            $rejected = ($link['verification'] ?? '') === 'rejected_nq1663';
            $records[] = [
                'old_province' => $link['oldProvince'] ?? '', 'old_district' => $link['oldDistrict'] ?? '',
                'old_name' => $link['oldName'] ?? null, 'new_code' => $unit['code'], 'new_name' => $unit['name'],
                'new_province' => $unit['province'], 'scope' => $link['scope'] ?? null,
                'source_relation' => $link['sourceRelation'] ?? null,
                'verified' => $trusted && ($link['verification'] ?? '') === 'verified_nq1663', 'enabled' => ! $rejected,
                'note' => $link['notes'] ?? '', 'evidence' => GeographyStore::evidence($link['evidence'] ?? null),
                'source_row' => $link['sourceRow'] ?? null,
            ];
        }

        return [$records, $notes, $data['snapshotDate']];
    }

    private function fromExcel(string $path): array
    {
        // Only read tabular relationships. Do not infer old wards by splitting prose from a catalogue.
        try {
            $zip = new \ZipArchive;
            if ($zip->open($path) === true) {
                $expanded = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $expanded += $zip->statIndex($i)['size'];
                }
                $tooLarge = $expanded > 128 * 1024 * 1024 || $zip->numFiles > 5000;
                $zip->close();
                if ($tooLarge) {
                    $this->invalid('Excel quá lớn sau giải nén. Chia nhỏ file trước khi nhập.');
                }
            }
            $type = IOFactory::identify($path);
            if (! in_array($type, ['Xlsx', 'Xls', 'Csv'], true)) {
                $this->invalid('Chỉ nhận Excel, CSV hoặc JSON chuẩn hóa.');
            }
            $reader = IOFactory::createReader($type);
            $reader->setReadDataOnly(true);
            $book = $reader->load($path);
            $sheet = $book->getSheet(0);
            if ($sheet->getHighestDataRow() > 30001 || \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()) > 26) {
                $this->invalid('Bảng nhập vượt giới hạn 30.000 dòng / 26 cột.');
            }
            $values = $sheet->toArray(null, false, false, false);
            $book->disconnectWorksheets();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->invalid('Không đọc được Excel. Dùng file liên kết cũ–mới hoặc JSON đã chuẩn hóa.');
        }
        $headers = array_map(fn ($v) => str_replace(' ', '_', GeographyStore::searchText((string) $v)), array_shift($values) ?? []);
        $original = ($headers[0] ?? '') === 'phuong_xa_cu' && ($headers[6] ?? '') === 'ma_phuong_xa_moi';
        if ($original && array_slice($headers, 0, 8) !== ['phuong_xa_cu', 'quan_huyen_cu', 'tinh_tp_cu_truoc_sap_nhap', 'tinh_tp_moi', 'phuong_xa_moi_tu_1_7_2025', 'loai_don_vi_moi', 'ma_phuong_xa_moi', 'hinh_thuc_sap_nhap']) {
            $this->invalid('Thứ tự cột Excel đã thay đổi. Chọn bảng đúng mẫu để tránh ghép sai địa bàn.');
        }
        $fields = ['tinh_cu', 'huyen_cu', 'xa_cu', 'tinh_moi', 'xa_moi', 'ma_xa_moi'];
        if (! $original && count(array_diff($fields, $headers))) {
            $this->invalid('File này không có đủ liên kết cũ–mới. Chọn vietnam-sap-nhap-phuong-xa.xlsx, bộ toàn quốc có sẵn, hoặc bảng gồm: tinh_cu, huyen_cu, xa_cu, tinh_moi, xa_moi, ma_xa_moi.');
        }
        $records = [];
        $notes = [];
        foreach ($values as $i => $raw) {
            if (! count(array_filter($raw, fn ($v) => $v !== null && trim((string) $v) !== ''))) {
                continue;
            }
            $row = array_combine($headers, array_pad(array_slice($raw, 0, count($headers)), count($headers), null));
            $read = fn ($key, $column) => trim((string) ($original ? ($raw[$column] ?? '') : ($row[$key] ?? '')));
            $code = $read('ma_xa_moi', 6);
            if (ctype_digit($code) && strlen($code) < 5) {
                $code = str_pad($code, 5, '0', STR_PAD_LEFT);
            }
            $record = ['old_province' => $read('tinh_cu', 2), 'old_district' => $read('huyen_cu', 1),
                'old_name' => $read('xa_cu', 0) ?: null, 'new_code' => $code,
                'new_name' => $read('xa_moi', 4), 'new_province' => $read('tinh_moi', 3),
                'source_relation' => $read('pham_vi', 7), 'scope' => null, 'enabled' => true, 'verified' => false,
                'note' => $original ? '' : (string) ($row['ghi_chu'] ?? ''), 'evidence' => null, 'source_row' => $i + 2];
            if (Validator::make($record, $this->rules())->fails()) {
                $notes[] = ['source' => 'Excel', 'row' => $i + 2, 'location' => implode(' / ', array_slice($record, 0, 6)), 'message' => 'Bỏ qua: thiếu thông tin định danh hoặc mã mới không đủ 5 chữ số.'];

                continue;
            }
            $records[] = $record;
        }

        return [$records, $notes, '2025-07-01', strtolower($type)];
    }

    private function rules(): array
    {
        return ['old_province' => 'required|string|max:255', 'old_district' => 'required|string|max:255',
            'old_name' => 'nullable|string|max:255', 'new_code' => ['required', 'string', 'regex:/^\d{5}$/D'],
            'new_name' => 'required|string|max:255', 'new_province' => 'required|string|max:255',
            'scope' => 'nullable|in:whole,part,remainder', 'note' => 'nullable|string|max:10000',
            'source_relation' => 'nullable|string|max:255', 'source_row' => 'nullable|integer|min:1'];
    }

    private function cleanRows(array $records, array &$notes): array
    {
        $rows = [];
        $codes = [];
        foreach ($records as $row) {
            Validator::make($row, $this->rules())->validate();
            foreach (['old_province', 'old_district', 'old_name', 'new_name', 'new_province'] as $field) {
                if ($row[$field] !== null) {
                    $row[$field] = trim(preg_replace('/\s+/u', ' ', $row[$field]));
                }
            }
            if (! $row['old_name'] && ! $row['note']) {
                $row['note'] = 'Nguồn không ghi xã/phường cũ; chỉ xác định cấp huyện.';
            }
            $row['old_key'] = GeographyStore::identity($row);
            $pair = $row['old_key'].'|'.$row['new_code'];
            $name = [$row['new_name'], $row['new_province']];
            if (isset($codes[$row['new_code']]) && $codes[$row['new_code']] !== $name) {
                $this->invalid('Một mã mới có nhiều tên/tỉnh khác nhau: '.$row['new_code']);
            }
            $codes[$row['new_code']] = $name;
            if (isset($rows[$pair])) {
                if ($rows[$pair]['enabled'] !== $row['enabled'] || $rows[$pair]['scope'] !== $row['scope']) {
                    $this->invalid('Liên kết trùng nhưng khác nội dung tại dòng '.$row['source_row']);
                }
                $notes[] = ['source' => 'Nhập dữ liệu', 'row' => $row['source_row'], 'location' => $row['old_name'], 'message' => 'Bỏ qua liên kết trùng.'];

                continue;
            }
            $row['search_text'] = GeographyStore::searchText(implode(' ', [$row['old_province'], $row['old_district'], $row['old_name'], $row['new_code'], $row['new_name'], $row['new_province']]));
            $row['updated_at'] = now();
            $rows[$pair] = $row;
        }

        return array_values($rows);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
