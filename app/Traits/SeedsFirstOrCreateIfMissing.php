<?php

namespace App\Traits;

use App\Enums\LookupsEnum;
use App\Models\Lookup;
use Illuminate\Database\Eloquent\Model;

trait SeedsFirstOrCreateIfMissing
{
    /**
     * Loads existing rows by `code` (and optional scope) once, then runs `firstOrCreate` only for missing codes.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, array<string, mixed>>  $definitions  Each row must include `code` and all attributes for the create payload (except `code` is also used in the match array).
     * @param  array<string, mixed>  $scopeWhere  Extra match attributes (e.g. `['key' => LookupsEnum::…]` for lookups).
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

            $first = array_merge($scopeWhere, ['code' => $code]);

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
