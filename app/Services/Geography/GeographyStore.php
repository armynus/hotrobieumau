<?php

namespace App\Services\Geography;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeographyStore
{
    public function db()
    {
        return DB::connection(config('geography.connection'));
    }

    public function ready(): bool
    {
        return $this->db()->getSchemaBuilder()->hasTable('geography_records');
    }

    public function current(): ?object
    {
        return $this->ready() ? $this->db()->table('geography_imports')->orderByDesc('id')->first() : null;
    }

    public function rows(?int $import = null)
    {
        return $this->db()->table('geography_records')->where('import_id', $import ?? $this->current()?->id ?? 0);
    }

    public static function searchText(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($value, 'vi'))));
    }

    public static function identity(array $row): string
    {
        // Accent folding is for searching only, never identity matching.
        return hash('sha256', json_encode([$row['old_province'], $row['old_district'], $row['old_name']], JSON_UNESCAPED_UNICODE));
    }

    public static function scope(?string $scope): string
    {
        return ['whole' => 'Toàn bộ', 'part' => 'Một phần', 'remainder' => 'Phần còn lại'][$scope ?? ''] ?? '';
    }

    public static function evidence(?string $url): ?string
    {
        $parts = parse_url($url ?? '');
        $host = strtolower($parts['host'] ?? '');

        return is_array($parts) && ($parts['scheme'] ?? '') === 'https' && ! isset($parts['user']) && ! isset($parts['pass'])
            && (str_ends_with($host, '.gov.vn') || $host === 'chinhphu.vn' || str_ends_with($host, '.chinhphu.vn')) ? $url : null;
    }
}
