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

    protected function getOrderByColumn()
    {
        if (!request()->filled('sortBy')) {
            return 'created_at';
        }

        $column = request('sortBy');

        $mapping = [
            'previous_policy_expiry_date_formatted' => 'previous_policy_expiry_date',
        ];

        return $mapping[$column] ?? $column;
    }
}
