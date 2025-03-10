<?php

namespace App\Traits;

trait Filterable
{
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

    private function applyFilter($query, $column, $id, $alias = null)
    {
        return $query->when(! empty($this->resolveIds($id)), function ($subQuery) use ($column, $id, $alias) {
            $subQuery->whereIn("{$this->getAlias($alias)}.{$column}", $this->resolveIds($id));
        });
    }

    public function scopeFilterByAdvisors($query, $id, $alias = null)
    {
        return $this->applyFilter($query, 'advisor_id', $id, $alias);
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

    public function scopeFilterBy($query, $filterName, $column = null, bool $ignoreAll = false)
    {
        $column = $column ?: $filterName;

        $filterValue = request($filterName, '');

        $hasFilter = ! empty($filterValue) && ! is_null($filterValue);

        if ($ignoreAll && $hasFilter && is_string($filterValue)) {
            $hasFilter = $hasFilter && strtolower($filterValue) !== 'all';
        }

        $query->when($hasFilter, function ($subQuery) use ($filterValue, $column) {
            $subQuery->where($column, $filterValue);
        });
    }

    public function scopeMatchBy($query, $filterName, $column = null)
    {
        $column = $column ?: $filterName;

        $filterValue = request($filterName, '');

        $hasFilter = ! empty($filterValue) && ! is_null($filterValue);

        $query->when($hasFilter, function ($subQuery) use ($filterValue, $column) {
            $subQuery->where($column, 'like', "%{$filterValue}%");
        });
    }
}
