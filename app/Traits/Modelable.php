<?php

namespace App\Traits;

trait Modelable
{
    public function scopeOptions($query, $column = 'text')
    {
        return $query->get()
            ->map(function ($department) use($column) {
                return [
                    'value' => $department->id,
                    'label' => $department->{$column},
                ];
            })->toArray();
    }
}
