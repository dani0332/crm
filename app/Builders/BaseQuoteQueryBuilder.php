<?php

namespace App\Builders;

abstract class BaseQuoteQueryBuilder
{
    use QueryBuildable;

    public function __construct(protected $model) {}

    protected function baseQuery(array $selection = [], ?array $relations = null)
    {
        return $this->model::select($selection)
            ->when($relations, function ($query) use ($relations) {
                return $query->with($relations);
            });
    }
}
