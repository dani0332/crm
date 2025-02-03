<?php

namespace App\Traits;

trait Modelable
{
    public function scopeOptions($query, $column = 'text')
    {
        return $query->get()
            ->map(function ($model) use ($column) {
                return [
                    'value' => $model->id,
                    'label' => $model->{$column},
                ];
            })->toArray();
    }
}
