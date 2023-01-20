<?php

namespace App\Http\Livewire;

use App\Enums\GenericRequestEnum;
use App\Models\CarQuote;
use App\Models\Tier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class AdvisorPerformanceReportTable extends DataTableComponent
{
    public $url;
    public $tiers = [];
    public $teams = [];
    public $leadSources = [];
    private $maxDays = 92;
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setColumnSelectDisabled()
            ->setFilterLayoutSlideDown()
            ->setPaginationDisabled()
            ->setFooterEnabled()
            ->setFooterTdAttributes(function ($rows) {
                return [
                    'default' => true,
                    'class' => 'font-black',
                    'style' => 'color:black;font-weight:900 !important;',
                ];
            });
    }

    public function mount()
    {
        $this->maxDays = $this->applicationStorageService->getValueByKey(GenericRequestEnum::MAX_DAYS);
        $this->tiers = Tier::query()
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $this->teams = collect(DB::select("
            WITH RECURSIVE teams_cte (id, name, parent_team_id, type, depth) AS (
                SELECT id, concat(name,' - (Team)') as name, parent_team_id, type, 0 as depth FROM teams WHERE parent_team_id = (SELECT id FROM teams WHERE name = 'Car')
                AND is_active = true
                UNION ALL
                SELECT t.id, concat(t.name,' - (Subteam)') as name, t.parent_team_id, t.type, cte.depth + 1 as depth FROM teams_cte cte
                JOIN teams t ON t.parent_team_id = cte.id
                AND t.is_active = true
            )
            SELECT * FROM teams_cte ORDER BY depth;"))
            ->keyBy('id')
            ->map(fn ($Teams) => $Teams->name)
            ->toArray();

        $this->leadSources = CarQuote::query()
            ->select('source as name')
            ->distinct()
            ->whereNotNull('source')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        if (! $this->getAppliedFilterWithValue('created_at')) {
            $this->setFilter('created_at', now()->subDays(90)->format('Y-m-d').'~'.now()->format('Y-m-d'));
        }
    }

    public function columns(): array
    {
        return [
            Column::make('Advisor Name', 'advisor.name')->searchable(),
            Column::make('Created Manually')->label(fn ($row) => $row->manual_created)->footer(function ($rows) {
                return $rows->sum('manual_created');
            }),
            Column::make('Auto Assigned')->label(fn ($row) => $row->auto_assigned)->footer(function ($rows) {
                return $rows->sum('auto_assigned');
            }),
            Column::make('Manually Assigned')->label(fn ($row) => $row->manually_assigned)->footer(function ($rows) {
                return $rows->sum('manually_assigned');
            }),
            Column::make('Total Leads')->label(fn ($row) => $row->total_leads)->footer(function ($rows) {
                return $rows->sum('total_leads');
            }),
            Column::make('View Count')->label(fn ($row) => $row->view_count)->footer(function ($rows) {
                return $rows->sum('view_count');
            }),
            Column::make('NI')->label(fn ($row) => $row->not_interested)->footer(function ($rows) {
                return $rows->sum('not_interested');
            }),
            Column::make('In Progress')->label(fn ($row) => $row->in_progress)->footer(function ($rows) {
                return $rows->sum('in_progress');
            }),
            Column::make('Bad Lead')->label(fn ($row) => $row->bad_leads)->footer(function ($rows) {
                return $rows->sum('bad_leads');
            }),
            Column::make('Sale')->label(fn ($row) => $row->sale_leads)->footer(function ($rows) {
                return $rows->sum('sale_leads');
            }),
        ];
    }

    public function builder(): Builder
    {
        return CarQuote::query()
            ->select(
                DB::raw('count(car_quote_request.id) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 40 THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('la.auto_assignment_count as auto_assigned'),
                DB::raw('la.manual_assignment_count as manually_assigned'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 8 THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (2,24,25) THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (9,35) THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(quote_view_count.visit_count) as view_count'),
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->leftJoin('lead_allocation as la', 'la.user_id', 'users.id')
            ->leftJoin('quote_view_count', 'quote_view_count.quote_id', 'car_quote_request.id')
            ->leftJoin('teams', function ($join) {
                $join->on('users.team_id', '=', 'teams.id');
                $join->on('users.sub_team_id', '=', 'teams.id');
            })
            ->whereNull('car_quote_request.renewal_import_code')
            ->groupBy('car_quote_request.advisor_id')
            ->orderBy('users.email');
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Created Date', 'created_at')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => $this->maxDays,
                ])
                ->filter(function (Builder $builder, string $value) {
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})~(\d{4}-\d{2}-\d{2})$/', $value, $matches)) {
                        $builder->whereBetween('car_quote_request.created_at', [$matches[1], $matches[2]]);
                    }
                }),
            SelectFilter::make('Teams')
                ->options($this->teams)->filter(function (Builder $builder, $value) {
                    $builder->where('teams.id', $value);
                }),
            SelectFilter::make('Tiers')
                ->options($this->tiers)->filter(function (Builder $builder, $value) {
                    $builder->where('car_quote_request.tier_id', $value);
                }),
            MultiSelectFilter::make('Lead Source')
                ->options($this->leadSources)->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.source', $value);
                }),

        ];
    }
}
