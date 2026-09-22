<?php

namespace App\Imports;

use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

abstract class TenantBatchImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public int $inserted = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $processed = 0;

    public function __construct(private readonly ?Closure $progress = null) {}

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    abstract protected function keyColumn(): string;

    /** @return list<string> */
    abstract protected function importedColumns(): array;

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    abstract protected function mergeRow(array $row, array $current, bool $exists): array;

    public function collection(Collection $rows): void
    {
        foreach ($rows->chunk($this->batchSize()) as $batch) {
            $this->persistBatch($batch);
            $this->processed += $batch->count();
            if ($this->progress) {
                ($this->progress)($this);
            }
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 500;
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function persistBatch(Collection $rows): void
    {
        $prepared = $rows->map(function ($row): ?array {
            $row = $row instanceof Arrayable ? $row->toArray() : (is_array($row) ? $row : (array) $row);
            $key = $this->normalizeValue($row[$this->keyColumn()] ?? null);

            if ($key === null) {
                $this->skipped++;

                return null;
            }

            return ['key' => (string) $key, 'row' => $row];
        })->filter()->values();

        if ($prepared->isEmpty()) {
            return;
        }

        $modelClass = $this->modelClass();
        $model = new $modelClass;
        $keyColumn = $this->keyColumn();
        $columns = $this->importedColumns();
        $keys = $prepared->pluck('key')->unique()->values()->all();

        [$inserted, $updated] = DB::connection($model->getConnectionName())->transaction(
            function () use ($prepared, $model, $keyColumn, $columns, $keys): array {
                $existing = $model->newQuery()
                    ->whereIn($keyColumn, $keys)
                    ->orderBy('id')
                    ->get()
                    ->unique(fn (Model $record) => (string) $record->getAttribute($keyColumn))
                    ->keyBy(fn (Model $record) => (string) $record->getAttribute($keyColumn));

                $states = [];
                $existingKeys = [];
                foreach ($prepared as $item) {
                    $key = $item['key'];
                    $record = $existing->get($key);
                    $exists = $record !== null;

                    if (! array_key_exists($key, $states)) {
                        $states[$key] = $exists
                            ? $record->getAttributes()
                            : array_fill_keys(array_merge([$keyColumn], $columns), null);
                        $states[$key][$keyColumn] = $key;
                    }

                    if ($exists) {
                        $existingKeys[$key] = true;
                    }

                    $states[$key] = $this->mergeRow($item['row'], $states[$key], $exists);
                    $states[$key][$keyColumn] = $key;
                }

                $now = now();
                $updates = [];
                $inserts = [];
                foreach ($states as $key => $state) {
                    $values = array_intersect_key($state, array_flip(array_merge(['id', $keyColumn], $columns)));
                    $values['updated_at'] = $now;

                    if (isset($existingKeys[$key])) {
                        $updates[] = $values;
                    } else {
                        unset($values['id']);
                        $values['created_at'] = $now;
                        $inserts[] = $values;
                    }
                }

                if ($updates !== []) {
                    $model->newQuery()->upsert($updates, ['id'], array_merge($columns, ['updated_at']));
                }
                if ($inserts !== []) {
                    $model->newQuery()->insert($inserts);
                }

                return [count($inserts), count($updates)];
            }
        );

        $this->inserted += $inserted;
        $this->updated += $updated;
    }

    protected function hasValue(array $row, string $column): bool
    {
        return array_key_exists($column, $row) && $this->normalizeValue($row[$column]) !== null;
    }

    protected function value(array $row, string $column): mixed
    {
        return $this->normalizeValue($row[$column] ?? null);
    }

    protected function dateValue(array $row, string $column): ?string
    {
        if (! $this->hasValue($row, $column)) {
            return null;
        }

        $value = $row[$column];
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $plain = trim((string) $value);
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $plain, $matches)) {
            return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])
                ? "{$matches[1]}-{$matches[2]}-{$matches[3]}"
                : null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $plain, $matches)) {
            return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $plain : null;
        }

        if (is_numeric($value) && (float) $value > 0 && (float) $value < 2958466) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $values */
    protected function fullAddress(array $values): ?string
    {
        $parts = array_values(array_filter(
            array_map(fn ($value) => $this->normalizeValue($value), $values),
            fn ($value) => $value !== null
        ));

        return $parts === [] ? null : implode(' ', $parts);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        return $value;
    }
}
