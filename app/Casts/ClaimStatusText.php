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
     * Handles both string input and array input (from get's return: ['value' => ..., 'label' => ...])
     * to prevent TypeError when the cast attribute is written back (e.g. model cloning).
     *
     * When the incoming value is null or an empty string, we persist NULL to the database
     * instead of an empty string to keep nullability semantics consistent with queries
     * like whereNull('text').
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            $value = $value['value'];
        }

        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return null;
        }

        return strtolower($stringValue);
    }
}
