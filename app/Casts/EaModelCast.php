<?php

namespace App\Casts;

use App\Enums\EaModelEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class EaModelCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EaModelEnum
    {
        if ($value === null || $value === '') {
            return null;
        }

        return EaModelEnum::tryFrom($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value instanceof EaModelEnum) {
            return $value->value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return EaModelEnum::tryFrom((string) $value)?->value;
    }
}
