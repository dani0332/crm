<?php

namespace App\Traits;

trait Arrayable
{
    public static function asArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->name] = $case->value;
        }

        return $result;
    }
}

