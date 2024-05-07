<?php

namespace App\Enums;

use Illuminate\Support\Str;

trait Enumable
{
    public function label()
    {
        return Str::title(Str::lower(str_replace('_', ' ', $this->name)));
    }

    public static function withLabels(): array
    {
        $values = [];

        foreach (self::cases() as $case) {
            $values[] = [
                'value' => $case->value,
                'label' => $case->label(),
            ];
        }

        return $values;
    }
}
