<?php

namespace App\Casts;

use App\Enums\ClaimsEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ClaimStatusText implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (empty($value)) {
            return null;
        } 
        $lowercased = strtolower(trim((string) $value));
        $enum = ClaimsEnum::tryFrom($lowercased);

        if (! $enum) {
            return [
                'value' => $value,
                'label' => $value,

            ];
        }

        return $enum->withLabel();
    }

     /**
     * Transform the attribute to its underlying model values.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return strtolower($value ?? '');
    }
}
