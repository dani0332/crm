<?php

namespace App\Traits;

trait Optionable
{
    public static function getOptions(string $valueColumn = 'id', string $labelColumn = 'text', bool $withActive = true, bool $active = false, bool $withAll = false, bool $sort = false)
    {
        $records = self::select("{$valueColumn} as value", "{$labelColumn} as label")
            ->when($withActive, function ($query) {
                $query->withActive();
            })
            ->when($active, function ($query) {
                $query->active();
            })
            ->when($sort, function ($query) {
                $query->orderBy('sort_order');
            })
            ->get();

        if ($withAll) {
            $records->prepend(['value' => 'all', 'label' => 'All']);
        }

        return $records;
    }
}
