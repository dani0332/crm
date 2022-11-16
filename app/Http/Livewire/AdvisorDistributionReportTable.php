<?php

namespace App\Http\Livewire;

use App\Models\CarQuote;
use App\Models\Team;
use App\Models\Tier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AdvisorDistributionReportTable extends DataTableComponent
{
    public $url;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
          ->setColumnSelectDisabled()
          ->setPerPageVisibilityDisabled()
          ->setFilterLayoutSlideDown()
          ->setPaginationVisibilityDisabled()
          ->setConfigurableAreas([
              'after-pagination' => 'partials.pagination',
          ])
          ->setFooterEnabled()
          ->setFooterTdAttributes(function ($rows) {
              return [
                  'default' => true,
                  'class' => 'font-black',
                  'style' => 'color:black;font-weight:900 !important;',
              ];
          });
    }

    public function columns(): array
    {
        return [
            Column::make('Advisor Name', 'advisor.name')->searchable(),
            Column::make('Total Leads')->label(fn ($row) => $row->total_leads)->footer(function ($rows) {
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
            Column::make('Tier 6 Lead Count')->label(fn ($row) => $row->tier_6_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_6_lead_count');
            }),
            Column::make('Tier H Lead Count')->label(fn ($row) => $row->tier_h_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_h_lead_count');
            }),
            Column::make('Tier L Lead Count')->label(fn ($row) => $row->tier_l_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_l_lead_count');
            }),
            Column::make('Total Lead Cost')->label(fn ($row) => $row->total_lead_cost)->footer(function ($rows) {
                return $rows->sum('total_lead_cost');
            }),
        ];
    }

    public function builder(): Builder
    {
        return CarQuote::query()
          ->select(
              DB::raw("count(car_quote_request.id) as total_leads"),
              DB::raw("SUM(CASE WHEN tiers.name = 'T0' THEN 1 ELSE 0 END) as tier_0_lead_count"),
              DB::raw("SUM(CASE WHEN tiers.name = 'T1' THEN 1 ELSE 0 END) as tier_1_lead_count"),
              DB::raw("SUM(CASE WHEN tiers.name = 'T2' THEN 1 ELSE 0 END) as tier_2_lead_count"),
              DB::raw("SUM(CASE WHEN tiers.name = 'T3' THEN 1 ELSE 0 END) as tier_3_lead_count"),
              DB::raw("SUM(CASE WHEN tiers.name = 'T4' THEN 1 ELSE 0 END) as tier_4_lead_count"),
              DB::raw("SUM(CASE WHEN tiers.name = 'T5' THEN 1 ELSE 0 END) as tier_5_lead_count"),
              DB::raw("SUM(CASE WHEN tiers.name = 'T6' THEN 1 ELSE 0 END) as tier_6_lead_count"),
              DB::raw("SUM(CASE WHEN tiers.name = 'TL' THEN 1 ELSE 0 END) as tier_l_lead_count"),
              DB::raw("SUM(CASE WHEN tiers.name = 'TH' THEN 1 ELSE 0 END) as tier_h_lead_count"),
              DB::raw('SUM(tiers.cost_per_lead) as total_lead_cost'),
          )
          ->join('users', 'users.id', 'car_quote_request.advisor_id')
          ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
          ->groupBy('users.email')
          ->orderBy('users.name');
    }

    // custom pagination

    public function getCurrentPage()
    {
        return $this->page;
    }

    protected function executeQuery()
    {
        return $this->getBuilder()->simplePaginate($this->getPerPage(), ['*'], $this->getComputedPageName());
    }

    public function filters(): array
    {
        $teams = Team::query()
        ->orderBy('name')
        ->get()
        ->keyBy('id')
        ->map(fn ($team) => $team->name)
        ->toArray();
        array_unshift($teams, [''=> 'All']);
        return [
            DateFilter::make('Start Date')
              ->filter(function (Builder $builder, string $value) {
                  $builder->whereDate('car_quote_request.created_at', '>=', $value);
              }),
            DateFilter::make('Stop Date')
              ->filter(function (Builder $builder, string $value) {
                  $builder->whereDate('car_quote_request.created_at', '<=', $value);
              }),
            SelectFilter::make('Teams')
            ->options(Team::query()
            ->orderBy('name')
            ->get()
            ->keyBy('id')
            ->map(fn ($team) => $team->name)
            ->toArray())->filter(function (Builder $builder, $value) {
                $builder->where('users.team_id', $value);
            }),
            SelectFilter::make('Tiers')
            ->options(
                Tier::query()
                    ->orderBy('name')
                    ->where('is_active', 1)
                    ->get()
                    ->keyBy('id')
                    ->map(fn ($tier) => $tier->name)
                    ->toArray(),
            )->filter(function (Builder $builder, $value) {
                $builder->where('car_quote_request.tier_id', $value);
            }),

        ];
    }
}
