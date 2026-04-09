<?php

namespace App\Services\Reports;

use App\Enums\ConversionOptimizationCapPercentageEnum;
use App\Enums\TeamNameEnum;
use App\Enums\quoteTypeCode;
use App\Models\Team;
use App\Models\User;
use App\Repositories\QuoteTypeRepository;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConversionOptimizationReportService extends AdvisorConversionReportService
{
    public function getReportData($request)
    {
        $builder = $this->getReportQueryBuilder($request);
        if ($builder === null) {
            return [];
        }

        LoggerService::sql(self::class.' - Conversion Optimization Report (base query)', $builder);

        $baseReportData = $this->mapAdvisorConversionQueryResults(collect($builder->get()));

        if ($baseReportData->isEmpty()) {
            return [];
        }

        return $this->applyPostQueryCalculations($baseReportData, (array) $request->all())->values()->all();
    }

    /**
     * Merge default filter values into the request so {@see getReportData()} matches the first client render
     * when the browser sends no query parameters. Explicit request values override defaults.
     */
    public function mergeDefaultsIntoRequest(Request $request, array $defaultFilters): Request
    {
        return $request->duplicate(
            array_merge($defaultFilters, $request->query->all()),
            array_merge($defaultFilters, $request->request->all())
        );
    }

    public function getFilterOptions()
    {
        return array_merge(parent::getFilterOptions(), [
            'capPercentages' => ConversionOptimizationCapPercentageEnum::withLabels(),
        ]);
    }

    public function getDefaultFilters()
    {
        $defaultFilters = parent::getDefaultFilters();
        $dateFormat = config('constants.DATE_FORMAT_ONLY');

        $organicTeamId = Team::query()
            ->where('name', TeamNameEnum::ORGANIC)
            ->where('is_active', 1)
            ->value('id');

        $defaultSubTeamIds = Team::query()
            ->whereIn('name', [TeamNameEnum::VALUE, TeamNameEnum::VOLUME])
            ->whereIn('parent_team_id', [$organicTeamId])
            ->where('is_active', 1)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        return array_merge($defaultFilters, [
            'lob' => quoteTypeCode::Car,
            'advisorAssignedDates' => [
                now()->subWeeks(8)->startOfDay()->format($dateFormat),
                now()->endOfDay()->format($dateFormat),
            ],
            'teams' => $organicTeamId ? [(string) $organicTeamId] : [],
            'sub_teams' => $defaultSubTeamIds,
            'cap_percentage' => '',
        ]);
    }

    public function applyPostQueryCalculations(Collection $reportRows, array $filters): Collection
    {
        $advisorMetadata = $this->getAdvisorMetadata(
            $reportRows->pluck('advisorId')->filter()->map(fn ($advisorId) => (int) $advisorId)->unique()->values()->all(),
            $filters['lob'] ?? null
        );

        $normalizedRows = $reportRows->map(function ($row) use ($advisorMetadata) {
            $metadata = $advisorMetadata->get((int) $row->advisorId);

            $row->conversion = round((float) ($row->net_conversion ?? 0), 2);
            $row->team_id = $metadata?->team_id;
            $row->team_name = $metadata?->team_name;
            $row->sub_team_id = $metadata?->sub_team_id;
            $row->sub_team_name = $metadata?->sub_team_name;
            $row->original_max_capacity = isset($metadata?->max_capacity) ? (int) $metadata->max_capacity : null;
            $row->ranking = null;
            $row->team_average = null;
            $row->expected_sales = null;
            $row->required_sales = null;
            $row->new_conversion = null;
            $row->cap_limit = null;
            $row->total_average = null;

            return $row;
        });

        $capPercentage = $this->resolveCapPercentage($filters['cap_percentage'] ?? null);

        /** Commented for future use for multiple groups */
        // Former multi-cohort grouping: ranked separately per resolveCohortKey() (e.g. by sub-team when team filters empty).
        // Product requirement: one ranked list for the entire filtered dataset (filters already narrow rows).
        // $cohorts = $normalizedRows->groupBy(fn ($row) => $this->resolveCohortKey($row, $filters));
        //
        // foreach ($cohorts as $cohortRows) {
        $cohortRows = $normalizedRows;

        $rankedRows = $cohortRows
            ->sort(function ($leftRow, $rightRow) {
                $conversionComparison = $rightRow->conversion <=> $leftRow->conversion;

                if ($conversionComparison !== 0) {
                    return $conversionComparison;
                }

                $advisorNameComparison = strcmp((string) ($leftRow->advisor_name ?? ''), (string) ($rightRow->advisor_name ?? ''));

                if ($advisorNameComparison !== 0) {
                    return $advisorNameComparison;
                }

                return ((int) ($leftRow->quote_batch_id ?? 0)) <=> ((int) ($rightRow->quote_batch_id ?? 0));
            })
            ->values();

        $teamAverage = round((float) $rankedRows->avg(fn ($row) => (float) $row->conversion), 2);

        foreach ($rankedRows as $index => $row) {
            $row->ranking = $index + 1;
            $row->team_average = $teamAverage;

            if ((float) $row->conversion < $teamAverage && (float) $row->total_leads > 0) {
                $row->expected_sales = round(((float) $row->total_leads * $teamAverage) / 100, 2);
                $row->required_sales = round($row->expected_sales - (float) $row->sale_leads, 2);
                $row->new_conversion = round(($row->expected_sales / (float) $row->total_leads) * 100, 2);
            }
        }

        $this->applyCapLimitCalculations($rankedRows, $capPercentage);
        // } // end of foreach $cohorts

        if ($normalizedRows->isNotEmpty()) {
            $datasetAverageConversion = round(
                (float) $normalizedRows->avg(fn ($row) => (float) $row->conversion),
                2
            );

            foreach ($normalizedRows as $row) {
                $row->total_average = $datasetAverageConversion;
            }
        }

        return $rankedRows;
    }

    protected function getAdvisorMetadata(array $advisorIds, ?string $lob): Collection
    {
        if ($advisorIds === []) {
            return collect();
        }

        $leadAllocationQuoteTypeId = $this->resolveLeadAllocationQuoteTypeId($lob);
        $teamSubQuery = DB::table('user_team')
            ->select('user_id', DB::raw('MIN(team_id) as team_id'))
            ->groupBy('user_id');

        return User::query()
            ->select(
                'users.id',
                'users.sub_team_id',
                'sub_teams.name as sub_team_name',
                'advisor_team.team_id',
                'teams.name as team_name',
                'lead_allocation.max_capacity'
            )
            ->leftJoinSub($teamSubQuery, 'advisor_team', function ($join) {
                $join->on('advisor_team.user_id', '=', 'users.id');
            })
            ->leftJoin('teams', 'teams.id', '=', 'advisor_team.team_id')
            ->leftJoin('teams as sub_teams', 'sub_teams.id', '=', 'users.sub_team_id')
            ->leftJoin('lead_allocation', function ($join) use ($leadAllocationQuoteTypeId) {
                $join->on('lead_allocation.user_id', '=', 'users.id');

                if ($leadAllocationQuoteTypeId !== null) {
                    $join->where('lead_allocation.quote_type_id', '=', $leadAllocationQuoteTypeId);
                }
            })
            ->whereIn('users.id', $advisorIds)
            ->get()
            ->keyBy('id');
    }

    private function resolveLeadAllocationQuoteTypeId(?string $lob): ?int
    {
        if (empty($lob)) {
            return null;
        }

        $normalizedLob = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE], true)
            ? quoteTypeCode::Business
            : $lob;

        return QuoteTypeRepository::where('code', $normalizedLob)->value('id');
    }

    /*
    private function resolveCohortKey(object $row, array $filters): string
    {
        $selectedSubTeams = $this->normalizeSelectedIds($filters['sub_teams'] ?? []);

        if ($selectedSubTeams !== []) {
            return 'selected-sub-teams';
        }

        $selectedTeams = $this->normalizeSelectedIds($filters['teams'] ?? []);

        if ($selectedTeams !== []) {
            return 'selected-teams';
        }

        if (! empty($row->sub_team_id)) {
            return 'sub-team:'.$row->sub_team_id;
        }

        if (! empty($row->team_id)) {
            return 'team:'.$row->team_id;
        }

        return 'dataset';
    }

    private function normalizeSelectedIds(mixed $selectedValues): array
    {
        return collect(is_array($selectedValues) ? $selectedValues : [$selectedValues])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values()
            ->all();
    }
    */

    private function resolveCapPercentage(mixed $capPercentage): ?int
    {
        if ($capPercentage === null || $capPercentage === '') {
            return null;
        }

        return (int) $capPercentage;
    }

    private function applyCapLimitCalculations(Collection $rankedRows, ?int $capPercentage): void
    {
        if ($capPercentage === null || $capPercentage <= 0 || $rankedRows->isEmpty()) {
            return;
        }

        $cappedAdvisorCount = (int) ceil(($rankedRows->count() * $capPercentage) / 100);

        if ($cappedAdvisorCount <= 0) {
            return;
        }

        $cappedRows = $rankedRows->take(-$cappedAdvisorCount)->values();
        $worstRankedRow = $cappedRows->last();

        foreach ($cappedRows as $row) {
            if ($worstRankedRow !== null && (int) $row->advisorId === (int) $worstRankedRow->advisorId && (int) $row->quote_batch_id === (int) $worstRankedRow->quote_batch_id) {
                $row->cap_limit = 0;

                continue;
            }

            if ($row->original_max_capacity === null || (int) $row->original_max_capacity <= 0) {
                $row->cap_limit = null;

                continue;
            }

            $row->cap_limit = $this->roundReducedCapLimit(((int) $row->original_max_capacity * $capPercentage) / 100);
        }
    }

    private function roundReducedCapLimit(float $reducedCapLimit): int
    {
        $decimalPart = $reducedCapLimit - floor($reducedCapLimit);

        if (abs($decimalPart - 0.1) < 0.00001) {
            return (int) floor($reducedCapLimit);
        }

        return (int) ceil($reducedCapLimit);
    }
}
