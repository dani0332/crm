<?php

namespace App\Traits;

use Carbon\Carbon;

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

    public function scopeResolveData($query, bool $paginted = false, bool $forExport = false, bool $getTotalCount = false)
    {
        return $query
            ->when(
                $getTotalCount,
                fn ($q) => $q->count(),
                fn ($query) => $query->when(
                    $forExport,
                    fn ($q) => $q->get(),
                    fn ($q) => $q->when($paginted, fn ($sq) => $sq->simplePaginate(10)->withQueryString())
                )
            );
    }

    public function scopeFilterByCreatedAt($query, $start, $end, $alias = null)
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $defaultStartDate = now()->startOfDay();
        $defaultEndDate = now()->endOfDay();

        $start = $start ? Carbon::parse($start)->startOfDay() : $defaultStartDate;
        $end = $end ? Carbon::parse($end)->endOfDay() : $defaultEndDate;

        $start = $start->format($dateFormat);
        $end = $end->format($dateFormat);

        $query->when($start, function ($sq) use ($start, $alias) {
            $sq->where("{$this->getAlias($alias)}.created_at", '>=', $start);
        });

        $query->when($end, function ($sq) use ($end, $alias) {
            $sq->where("{$this->getAlias($alias)}.created_at", '<=', $end);
        });
    }
}
