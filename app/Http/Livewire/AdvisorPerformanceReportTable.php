<?php

namespace App\Http\Livewire;

use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\Team;
use App\Models\Tier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
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
              DB::raw('SUM(CASE WHEN car_quote_request_detail.advisor_assigned_by_id is null and car_quote_request.advisor_id is not null THEN 1 ELSE 0 END) as auto_assigned'),
              DB::raw('SUM(CASE WHEN car_quote_request_detail.advisor_assigned_by_id is not null and car_quote_request.advisor_id is not null THEN 1 ELSE 0 END) as manually_assigned'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 8 THEN 1 ELSE 0 END) as not_interested'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (2,24,25) THEN 1 ELSE 0 END) as in_progress'),
              DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" THEN 1 ELSE 0 END) as manual_created'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (9,35) THEN 1 ELSE 0 END) as bad_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) as sale_leads'),
              DB::raw('count(quote_view_count.visit_count) as view_count'),
          )
          ->join('users', 'users.id', 'car_quote_request.advisor_id')
          ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
          ->leftJoin('quote_view_count', 'quote_view_count.quote_id', 'car_quote_request.id')
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
