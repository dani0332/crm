<?php

namespace App\Traits;

use App\Builders\QueryBuildable;
use App\Enums\QuoteStatusEnum;
use Carbon\Carbon;

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

    public function scopeFilterByAdvisorAssignedDates($query, string $relation, array|string $filterNames, bool $verifyQuoteStatus = false)
    {
        $start = null;
        $end = null;

        if (is_array($filterNames)) {
            [$startDateFilterName, $endDateFilterName] = $filterNames;
            $start = request($startDateFilterName, null);
            $end = request($endDateFilterName, null);
        } elseif (is_string($filterNames) && request()->filled($filterNames)) {
            [$start, $end] = request($filterNames);
        }

        $start = $start ? $this->parseDate($start, true) : null;
        $end = $end ? $this->parseDate($end, false) : null;

        $query->when(! empty($start) && empty($end), function ($query) use ($relation, $start) {
            $query->whereRelation($relation, 'advisor_assigned_date', '>=', $start);
        })->when(empty($start) && ! empty($end), function ($query) use ($relation, $end) {
            $query->whereRelation($relation, 'advisor_assigned_date', '<=', $end);
        })->when(! empty($start) && ! empty($end), function ($query) use ($relation, $start, $end) {
            $query->whereHas($relation, function ($query) use ($start, $end) {
                $query->whereBetween('advisor_assigned_date', [$start, $end]);
            });
        })->when($verifyQuoteStatus, fn ($q) => $q->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]));
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

    public function applyByFilters($query, string $filterName, string $operator, ?string $column = null, bool $ignoreAll = false, bool $isBool = false)
    {
        $filterValue = request($filterName, ($operator === 'in' ? [] : ''));

        $hasFilter = request()->filled($filterName);

        if ($isBool) {
            $filterValue = filter_var($filterValue, FILTER_VALIDATE_BOOLEAN);
        }

        if ($operator === 'in' && ! is_array($filterValue) && ! empty($filterValue)) {
            $filterValue = explode(',', $filterValue);
        }

        if ($ignoreAll && $hasFilter) {
            if (is_array($filterValue)) {
                $hasFilter = ! in_array('all', $filterValue);
            } elseif (is_string($filterValue)) {
                $hasFilter = strtolower($filterValue) !== 'all';
            }
        }

        $column = $this->resolveColumn($filterName, $column);

        $query->when($hasFilter, function ($subQuery) use ($filterValue, $column, $operator) {
            if ($operator === '=') {
                $subQuery->where($column, $filterValue);
            } elseif ($operator === 'in') {
                $subQuery->whereIn($column, $filterValue);
            } elseif ($operator === 'like') {
                $subQuery->where($column, 'like', "%{$filterValue}%");
            }
        });
    }

    public function scopeFilterBy($query, string $filterName, ?string $column = null, bool $ignoreAll = false, bool $isBool = false)
    {
        $this->applyByFilters($query, $filterName, '=', $column, $ignoreAll, $isBool);
    }

    public function scopeFilterIn($query, string $filterName, ?string $column = null, bool $ignoreAll = false)
    {
        $this->applyByFilters($query, $filterName, 'in', $column, $ignoreAll);
    }

    public function scopeMatchBy($query, string $filterName, ?string $column = null, bool $ignoreAll = false)
    {
        $this->applyByFilters($query, $filterName, 'like', $column, $ignoreAll);
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

    public function scopeToday($query, $column = 'created_at')
    {
        $query->whereDate($column, today());
    }
}
