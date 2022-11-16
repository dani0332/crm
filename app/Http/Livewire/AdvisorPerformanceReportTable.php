<?php

namespace App\Http\Livewire;

use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\QuoteBatches;
use App\Models\Team;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AdvisorPerformanceReportTable extends DataTableComponent
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
            Column::make('Created Manually')->label(fn ($row) => $row->manual_created)->footer(function ($rows) {
                return $rows->sum('manual_created');
            }),
            // Column::make('Auto Assigned')->label(fn ($row) => $row->new_leads)->footer(function ($rows) {
            //     return $rows->sum('new_leads');
            // }),
            // Column::make('Manually Assigned')->label(fn ($row) => $row->not_interested)->footer(function ($rows) {
            //     return $rows->sum('not_interested');
            // }),
            // Column::make('Pulled Leads')->label(fn ($row) => $row->in_progress)->footer(function ($rows) {
            //     return $rows->sum('in_progress');
            // }),
            Column::make('Total Leads')->label(fn ($row) => $row->total_leads)->footer(function ($rows) {
                return $rows->sum('total_leads');
            }),
            // Column::make('Dials')->label(fn ($row) => $row->bad_leads)->footer(function ($rows) {
            //     return $rows->sum('bad_leads');
            // }),
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
            Column::make('Completed')->label(fn ($row) => $row->completed_leads)->footer(function ($rows) {
                return $rows->sum('completed_leads');
            }),
        ];
    }

    public function builder(): Builder
    {
        return CarQuote::query()
          ->select(
              DB::raw('count(car_quote_request.id) as total_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 40 THEN 1 ELSE 0 END) as new_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 8 THEN 1 ELSE 0 END) as not_interested'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (2,24,25) THEN 1 ELSE 0 END) as in_progress'),
              DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" THEN 1 ELSE 0 END) as manual_created'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (9,35) THEN 1 ELSE 0 END) as bad_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) as sale_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 15 THEN 1 ELSE 0 END) as completed_leads'),
          )
          ->join('users', 'users.id', 'car_quote_request.advisor_id')
          ->leftJoin('teams', function ($join) {
              $join->on('users.team_id', '=', 'teams.id');
              $join->on('users.sub_team_id', '=', 'teams.id');
          })
          ->whereNull('car_quote_request.renewal_import_code')
          ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
          ->orderBy('users.email');
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
            ->options(
                Team::query()
                    ->orderBy('name')
                    ->whereNotNull('parent_team_id')
                    ->where('parent_team_id', 2)
                    ->get()
                    ->keyBy('id')
                    ->map(fn ($Teams) => $Teams->name)

                    ->toArray(),
            )->filter(function (Builder $builder, $value) {
                $builder->where('teams.id', $value);
            }),
            SelectFilter::make('Tiers')
            ->options(
                Tier::query()
                    ->orderBy('name')
                    ->where('is_active', 1)
                    ->get()
                    ->keyBy('id')
                    ->map(fn ($users) => $users->name)
                    ->toArray(),
            )->filter(function (Builder $builder, $value) {
                $builder->where('car_quote_request.tier_id', $value);
            }),
            SelectFilter::make('Lead Source')
            ->options(
                LeadSource::query()
                    ->orderBy('name')
                    ->where('is_active', 1)
                    ->get()
                    ->keyBy('name')
                    ->map(fn ($users) => $users->name)
                    ->toArray(),
            )->filter(function (Builder $builder, $value) {
                $builder->where('car_quote_request.source', $value);
            }),

        ];
    }
}
