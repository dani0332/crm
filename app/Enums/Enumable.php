<?php

namespace App\Enums;

trait Enumable
{
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
