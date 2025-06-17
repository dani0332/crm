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

    protected function getOrderByColumn($requestParams = [])
    {
        // Helper method to get filter value from requestParams or request object
        $getFilterValue = function ($filterName) use ($requestParams) {
            if (! empty($requestParams) && isset($requestParams[$filterName])) {
                return $requestParams[$filterName];
            }

            return request($filterName);
        };

        if (! $getFilterValue('sortBy')) {
            return 'created_at';
        }

        $column = $getFilterValue('sortBy');

        $mapping = [
            'previous_policy_expiry_date_formatted' => 'previous_policy_expiry_date',
        ];

        return $mapping[$column] ?? $column;
    }
}
