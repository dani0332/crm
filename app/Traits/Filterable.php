<?php

namespace App\Traits;

use App\Builders\QueryBuildable;

trait Filterable
{
    use QueryBuildable;

    private function getAlias($alias = null)
    {
        return $alias ?: $this->getTable();
    }

    private function resolveIds($itemId): array
    {
        if (empty($itemId)) {
            return [];
        }

        return is_array($itemId) ? $itemId : [$itemId];
    }

    private function resolveColumn(string $filterName, ?string $column = null): string
    {
        return $column ?: $filterName;
    }

    private function applyFilter($query, $column, $id, $alias = null)
    {
        return $query->when(! empty($this->resolveIds($id)), function ($subQuery) use ($column, $id, $alias) {
            $subQuery->whereIn("{$this->getAlias($alias)}.{$column}", $this->resolveIds($id));
        });
    }

    public function scopeFilterByAdvisors($query, $id, $alias = null)
    {
        $ids = $this->resolveIds($id);

        $query->when(! empty($ids), function ($query) use ($ids, $alias) {
            if (in_array('-1', $ids) || in_array(-1, $ids)) {
                $query->whereNull('advisor_id');
            } else {
                $this->applyFilter($query, 'advisor_id', $ids, $alias);
            }
        });

    }

    public function scopeFilterByBatches($query, $id, $alias = null)
    {
        return $this->applyFilter($query, 'quote_batch_id', $id, $alias);
    }

    public function scopeFilterByTiers($query, $id, $alias = null)
    {
        return $this->applyFilter($query, 'tier_id', $id, $alias);
    }

    public function scopeFilterByTeams($query, $id, $alias = null)
    {
        $query->when(! empty($this->resolveIds($id)), function ($subQuery) use ($id, $alias) {
            $subQuery->whereIn("{$this->getAlias($alias)}.advisor_id", function ($sq) use ($id) {
                $sq->select('user_team.user_id')
                    ->from('user_team')
                    ->whereIn('user_team.team_id', $this->resolveIds($id));
            });
        });
    }

    public function scopeFilterBySubTeams($query, $id, $alias = null)
    {
        $query->when(! empty($this->resolveIds($id)), function ($subQuery) use ($id, $alias) {
            $subQuery->whereIn("{$this->getAlias($alias)}.advisor_id", function ($sq) use ($id) {
                $sq->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->whereIn('users.sub_team_id', $this->resolveIds($id));
            });
        });
    }

    public function scopeFilterByAdvisorAssignedDates($query, string $relation, array $filterNames)
    {
        [$startDateFilterName, $endDateFilterName] = $filterNames;

        $query->when(request()->filled($startDateFilterName) && ! request()->filled($endDateFilterName), function ($query) use ($relation, $startDateFilterName) {
            $query->whereRelation($relation, 'advisor_assigned_date', '>=', $this->parseDate(request($startDateFilterName), true));
        })->when(! request()->filled($startDateFilterName) && request()->filled($endDateFilterName), function ($query) use ($relation, $endDateFilterName) {
            $query->whereRelation($relation, 'advisor_assigned_date', '<=', $this->parseDate(request($endDateFilterName), false));
        })->when(request()->filled($startDateFilterName) && request()->filled($endDateFilterName), function ($query) use ($relation, $startDateFilterName, $endDateFilterName) {
            $query->whereHas($relation, function ($query) use ($startDateFilterName, $endDateFilterName) {
                $query->whereBetween('advisor_assigned_date', [$this->parseDate(request($startDateFilterName), true), $this->parseDate(request($endDateFilterName), false)]);
            });
        });
    }

    public function scopeFilterByPaymentDueDates($query, $filterName)
    {
        $query->when(request()->filled($filterName), function ($query) use ($filterName) {
            [$start, $end] = request($filterName);

            $start = $this->parseDate($start, true);
            $end = $this->parseDate($end, false);

            $query->whereHas('payments', function ($q) use ($start, $end) {
                $q->whereBetween('payment_due_date', [$start, $end]);
            });
        });
    }

    public function scopeFilterBy($query, $filterName, $column = null, bool $ignoreAll = false)
    {

        $filterValue = request($filterName, '');

        $hasFilter = ! empty($filterValue) && ! is_null($filterValue);

        if ($ignoreAll && $hasFilter && is_string($filterValue)) {
            $hasFilter = $hasFilter && strtolower($filterValue) !== 'all';
        }

        $column = $this->resolveColumn($filterName, $column);
        $query->when($hasFilter, function ($subQuery) use ($filterValue, $column) {
            $subQuery->where($column, $filterValue);
        });
    }

    public function scopeFilterIn($query, $filterName, $column = null, bool $ignoreAll = false)
    {
        $filterValue = request($filterName, []);

        if (! is_array($filterValue) && ! empty($filterValue)) {
            $filterValue = explode(',', $filterValue);
        }

        $hasFilter = ! empty($filterValue) && ! is_null($filterValue);

        if ($ignoreAll && $hasFilter && is_array($filterValue)) {
            $hasFilter = $hasFilter && ! in_array('all', $filterValue);
        }

        $column = $this->resolveColumn($filterName, $column);
        $query->when($hasFilter, function ($subQuery) use ($filterValue, $column) {
            $subQuery->whereIn($column, $filterValue);
        });
    }

    public function scopeMatchBy($query, $filterName, $column = null, bool $ignoreAll = false)
    {
        $filterValue = request($filterName, '');

        $hasFilter = ! empty($filterValue) && ! is_null($filterValue);

        if ($ignoreAll && $hasFilter && is_string($filterValue)) {
            $hasFilter = $hasFilter && strtolower($filterValue) !== 'all';
        }

        $column = $this->resolveColumn($filterName, $column);
        $query->when($hasFilter, function ($subQuery) use ($filterValue, $column) {
            $subQuery->where($column, 'like', "%{$filterValue}%");
        });
    }

    public function scopeFilterByToday($query, $column = 'created_at')
    {
        $query->whereBetween($column, [$this->parseDate(now(), true), $this->parseDate(now(), false)]);
    }

    public function scopeFilterByDateRange($query, $filterName, $column = null)
    {
        $column = $this->resolveColumn($filterName, $column);
        $query->when(request()->filled($filterName), function ($subQuery) use ($column, $filterName) {
            [$start, $end] = request($filterName);

            $start = $this->parseDate($start, true);
            $end = $this->parseDate($end, false);

            $subQuery->whereBetween($column, [$start, $end]);
        });
    }

    public function scopeFilterByDate($query, $filterName, $column = null, $isStartOfDay = true)
    {
        $column = $this->resolveColumn($filterName, $column);
        $query->when(request()->filled($filterName), function ($subQuery) use ($column, $filterName, $isStartOfDay) {
            $subQuery->where($column, $isStartOfDay ? '>=' : '<=', $this->parseDate(request($filterName), $isStartOfDay));
        });
    }
}
