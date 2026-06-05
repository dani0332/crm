<?php

namespace App\Traits;

use App\Enums\LookupsEnum;
use App\Models\Lookup;
use Illuminate\Database\Eloquent\Model;

trait SeedsIfMissing
{
    /**
     * Bulk upsert seed data. Requires a unique index on `code` (plus scope columns) on the target table.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, array<string, mixed>>  $definitions  Each row must include `code`.
     * @param  array<string, mixed>  $scopeWhere  Extra match columns merged into every row (e.g. `['key' => LookupsEnum::…]`).
     */
    protected function seedUpsertIfMissing(string $modelClass, array $definitions, array $scopeWhere = []): void
    {
        if ($definitions === []) {
            return;
        }

        $rows = array_map(fn (array $row) => [...$scopeWhere, ...$row], $definitions);

        $uniqueBy = ['code', ...array_keys($scopeWhere)];

        $updateColumns = array_keys(array_diff_key($rows[0], array_flip([...$uniqueBy, 'created_at'])));

        Model::unguarded(function () use ($modelClass, $rows, $uniqueBy, $updateColumns): void {
            $modelClass::upsert($rows, $uniqueBy, $updateColumns);
        });
    }

    /**
     * Row-by-row seed using `firstOrCreate`. Use for tables without a unique index on `code`.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, array<string, mixed>>  $definitions  Each row must include `code`.
     * @param  array<string, mixed>  $scopeWhere  Extra match columns (e.g. `['key' => LookupsEnum::…]`).
     */
    protected function seedFirstOrCreateIfMissing(string $modelClass, array $definitions, array $scopeWhere = []): void
    {
        if ($definitions === []) {
            return;
        }

        $codes = array_values(array_unique(array_column($definitions, 'code')));

        $query = $modelClass::query()->whereIn('code', $codes);
        foreach ($scopeWhere as $column => $value) {
            $query->where($column, $value);
        }

        $existingCodeIndex = array_flip($query->pluck('code')->all());

        foreach ($definitions as $row) {
            if (isset($existingCodeIndex[$row['code']])) {
                continue;
            }

            $code = $row['code'];
            $attributes = $row;
            unset($attributes['code']);

            $first = [...$scopeWhere, 'code' => $code];

            Model::unguarded(function () use ($modelClass, $first, $attributes): void {
                $modelClass::firstOrCreate($first, $attributes);
            });
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     */
    protected function seedLookupsIfMissing(LookupsEnum $key, array $definitions): void
    {
        $this->seedFirstOrCreateIfMissing(Lookup::class, $definitions, ['key' => $key]);
    }
}
