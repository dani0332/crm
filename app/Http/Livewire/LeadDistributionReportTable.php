<?php

namespace App\Http\Livewire;

use App\Models\CarQuote;
use App\Models\Tier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;

class LeadDistributionReportTable extends DataTableComponent
{
    public $url;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
          ->setColumnSelectDisabled()
          ->setPaginationDisabled()
          ->setFilterLayoutSlideDown()
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
            Column::make('Tier Name', 'tier.name')->sortable(),
            Column::make('Received Leads')->label(fn ($row) => $row->received_leads)->footer(function ($rows) {
                return $rows->sum('received_leads');
            }),
            Column::make('Leads Created')->label(fn ($row) => $row->lead_created)->footer(function ($rows) {
                return $rows->sum('lead_created');
            })->sortable(),
            Column::make('Total Leads')->label(fn ($row) => $row->lead_created + $row->received_leads)->footer(function ($rows) {
                return $rows->sum('lead_created') + $rows->sum('received_leads');
            })->sortable(),
            Column::make('UnAssigned Leads')->label(fn ($row) => $row->unassigned_leads)->footer(function ($rows) {
                return $rows->sum('unassigned_leads');
            })->sortable(),
            Column::make('Auto Assigned')->label(fn ($row) => $row->auto_assigned)->footer(function ($rows) {
                return $rows->sum('auto_assigned');
            })->sortable(),
            Column::make('Manually Assigned')->label(fn ($row) => $row->manually_assigned)->footer(function ($rows) {
                return $rows->sum('manually_assigned');
            })->sortable(),
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
              DB::raw('SUM(CASE WHEN car_quote_request_detail.advisor_assigned_by_id is null and car_quote_request.advisor_id is not null THEN 1 ELSE 0 END) as auto_assigned'),
              DB::raw('SUM(CASE WHEN car_quote_request_detail.advisor_assigned_by_id is not null and car_quote_request.advisor_id is not null THEN 1 ELSE 0 END) as manually_assigned'),
          )
          ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
          ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
          ->groupBy('tiers.name');
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
