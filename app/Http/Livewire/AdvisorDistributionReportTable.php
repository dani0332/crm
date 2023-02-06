<?php

namespace App\Http\Livewire;

use App\Enums\GenericRequestEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\Tier;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class AdvisorDistributionReportTable extends DataTableComponent
{
    public $url;
    public $tiers = [];
    public $teams = [];
    private $maxDays = 92;
    public function configure(): void
    {
        $this->setPrimaryKey('advisor.name')
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
        $this->maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $this->tiers = Tier::query()
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($tier) => $tier->name)
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
            ->map(fn ($team) => $team->name)
            ->prepend('All', '')
            ->toArray();

        if (! $this->getAppliedFilterWithValue('created_at')) {
            $this->setFilter('created_at', now()->subDays($this->maxDays)->format('d-m-Y').'~'.now()->format('d-m-Y'));
        }
    }

    public function columns(): array
    {
        return [
            Column::make('Advisor Name', 'advisor.name')->searchable(),
            Column::make('Total Leads')->label(fn ($row) => ($row->total_leads))->footer(function ($rows) {
                return $rows->sum('total_leads');
            }),
            Column::make('Tier 0 Lead Count')->label(fn ($row) => $row->tier_0_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_0_lead_count');
            }),
            Column::make('Tier 1 Lead Count')->label(fn ($row) => $row->tier_1_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_1_lead_count');
            }),
            Column::make('Tier 2 Lead Count')->label(fn ($row) => $row->tier_2_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_2_lead_count');
            }),
            Column::make('Tier 3 Lead Count')->label(fn ($row) => $row->tier_3_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_3_lead_count');
            }),
            Column::make('Tier 4 Lead Count')->label(fn ($row) => $row->tier_4_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_4_lead_count');
            }),
            Column::make('Tier 5 Lead Count')->label(fn ($row) => $row->tier_5_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_5_lead_count');
            }),
            Column::make('Tier 6 NON-ECOM COUNT')->label(fn ($row) => $row->tier_6_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_6_lead_count');
            }),
            Column::make('Tier 6 ECOM COUNT')->label(fn ($row) => $row->tier_6_lead_count_e)->footer(function ($rows) {
                return $rows->sum('tier_6_lead_count_e');
            }),
            Column::make('Tier H Lead Count')->label(fn ($row) => $row->tier_h_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_h_lead_count');
            }),
            Column::make('Tier L Lead Count')->label(fn ($row) => $row->tier_l_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_l_lead_count');
            }),
            Column::make('Tier R Lead Count')->label(fn ($row) => $row->tier_r_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_r_lead_count');
            }),
            Column::make('Total Lead Cost')->label(fn ($row) => $row->total_lead_cost)->footer(function ($rows) {
                return $rows->sum('total_lead_cost');
            }),
        ];
    }

    public function builder(): Builder
    {
        $query = CarQuote::query()
            ->select(
                DB::raw('count(*) as total_leads'),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 0' THEN 1 ELSE 0 END) as tier_0_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 1' THEN 1 ELSE 0 END) as tier_1_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 2' THEN 1 ELSE 0 END) as tier_2_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 3' THEN 1 ELSE 0 END) as tier_3_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 4' THEN 1 ELSE 0 END) as tier_4_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 5' THEN 1 ELSE 0 END) as tier_5_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 6 (non ecom)' THEN 1 ELSE 0 END) as tier_6_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 6 (Ecom)' THEN 1 ELSE 0 END) as tier_6_lead_count_e"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier L' THEN 1 ELSE 0 END) as tier_l_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier H' THEN 1 ELSE 0 END) as tier_h_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier R' AND tiers.is_active = 1 THEN 1 ELSE 0 END) as tier_r_lead_count"),
                DB::raw('SUM(car_quote_request.cost_per_lead) as total_lead_cost'),
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('user_team', 'user_team.user_id', 'users.id')
            ->leftJoin('teams', function ($join) {
                $join->on('users.team_id', '=', 'teams.id');
                $join->on('users.sub_team_id', '=', 'teams.id');
            })
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->where('tiers.name', 'not like', '%TR%')
            ->groupBy('users.email')
            ->orderBy('users.name');
        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            $query->where('users.id', auth()->user()->id);
        }

        return $query;
    }

    public function filters(): array
    {
        $filters = [
            TextFilter::make('Created Date', 'created_at')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => $this->maxDays,
                ])
                ->filter(function (Builder $builder, string $value) {
                    $dates = explode('~', $value);
                    $dates[0] = Carbon::parse($dates[0])->format('Y-m-d');
                    $dates[1] = Carbon::parse($dates[1])->format('Y-m-d');
                    $builder->whereBetween('car_quote_request.created_at', $dates);
                }),
        ];
        if (auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarDeputyManager, RolesEnum::Admin, RolesEnum::Engineering])) {
            array_push(
                $filters,
                SelectFilter::make('Teams')
                    ->options($this->teams)
                    ->filter(function (Builder $builder, $value) {
                        $builder->where('teams.id', $value);
                    }),
                SelectFilter::make('Tiers')
                    ->options($this->tiers)
                    ->filter(function (Builder $builder, $value) {
                        $builder->where('car_quote_request.tier_id', $value);
                    })
            );
        }

        return $filters;
    }
}
