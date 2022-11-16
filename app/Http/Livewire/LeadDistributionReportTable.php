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

class LeadDistributionReportTable extends DataTableComponent
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
            Column::make('Tier Name', 'tier.name')->searchable(),
            Column::make('Received Leads')->label(fn ($row) => $row->total_leads)->footer(function ($rows) {
                return $rows->sum('received_leads');
            }),
            Column::make('Leads Created')->label(fn ($row) => $row->lead_created)->footer(function ($rows) {
                return $rows->sum('lead_created');
            }),
            Column::make('Total Leads')->label(fn ($row) => $row->total_leads)->footer(function ($rows) {
                return $rows->sum('total_leads');
            }),
            Column::make('UnAssigned Leads')->label(fn ($row) => $row->unassigned_leads)->footer(function ($rows) {
                return $rows->sum('unassigned_leads');
            }),
        ];
    }

    public function builder(): Builder
    {
        return CarQuote::query()
          ->select(
              DB::raw('SUM(CASE WHEN car_quote_request.source not in ("Renewal_upload", "IMCRM", "TPL_RENEWALS") THEN 1 ELSE 0 END) as received_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" THEN 1 ELSE 0 END) as lead_created'),
              DB::raw('count(car_quote_request.id) as total_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.advisor_id is null THEN 1 ELSE 0 END) as unassigned_leads'),
          )
          ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
          ->groupBy('tiers.name');
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
            MultiSelectFilter::make('Tiers')
            ->options(
                Tier::query()
                    ->orderBy('name')
                    ->where('is_active', 1)
                    ->get()
                    ->keyBy('id')
                    ->map(fn ($users) => $users->name)
                    ->toArray(),
            )->filter(function (Builder $builder, $value) {
                $builder->whereIn('car_quote_request.tier_id', $value);
            }),

        ];
    }
}
