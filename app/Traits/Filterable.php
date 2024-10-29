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
}
