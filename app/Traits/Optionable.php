<?php

namespace App\Traits;

trait Optionable
{
    public static function getOptions(string $valueColumn = 'id', string $labelColumn = 'text', bool $withActive = true, bool $active = false)
    {
        return self::select("{$valueColumn} as value", "{$labelColumn} as label")
            ->when($withActive, function ($query) {
                $query->withActive();
            })
            ->when($active, function ($query) {
                $query->active();
            })
            ->get();
    }
}
