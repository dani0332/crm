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

class AdvisorConversionReportTable extends DataTableComponent
{
    public $url;

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

    public function columns(): array
    {
        return [
            Column::make('Batch Number', 'batch.name'),
            Column::make('Start Date', 'batch.start_date'),
            Column::make('Stop Date', 'batch.end_date'),
            Column::make('Advisor Name', 'advisor.name')->searchable(),
            Column::make('Total Leads')
            ->label(
                fn ($row, Column $column) => '<a x-on:click="window.livewire.emitTo(`table-modal`, `show`, ['. $row .', `total_leads`])" class="text-sky-700 cursor-pointer">'.$row->total_leads.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('total_leads');
            }),
            Column::make('New Leads')->label(
                fn ($row, Column $column) => '<a x-on:click="window.livewire.emitTo(`table-modal`, `show`, ['. $row .', `new_leads`])" class="text-sky-700 cursor-pointer">'.$row->new_leads.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('new_leads');
            }),
            Column::make('Not Interested')->label(
                fn ($row, Column $column) => '<a x-on:click="window.livewire.emitTo(`table-modal`, `show`, ['. $row .', `not_interested`])" class="text-sky-700 cursor-pointer">'.$row->not_interested.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('not_interested');
            }),
            Column::make('In Progress')->label(fn ($row) => $row->in_progress)->footer(function ($rows) {
                return $rows->sum('in_progress');
            }),
            Column::make('Manually Created')->label(fn ($row) => $row->manual_created)->footer(function ($rows) {
                return $rows->sum('manual_created');
            }),
            Column::make('Bad Leads')->label(fn ($row) => $row->bad_leads)->footer(function ($rows) {
                return $rows->sum('bad_leads');
            }),
            Column::make('Sale Leads')->label(fn ($row) => $row->sale_leads)->footer(function ($rows) {
                return $rows->sum('sale_leads');
            }),
            Column::make('Gross Conversion')->label(fn ($row) => (($row->sale_leads - $row->created_sale_leads) / (($row->total_leads - $row->manual_created) > 0 ? ($row->total_leads - $row->manual_created) : 1)).' %')
            ->footer(function ($rows) {
                $total = 0;
                foreach ($rows as $row) {
                    $total = $total + (($row->sale_leads - $row->created_sale_leads) / (($row->total_leads - $row->manual_created) > 0 ? ($row->total_leads - $row->manual_created) : 1));
                }

                return number_format((float) $total, 2, '.', '').' %';
            }),
            Column::make('Net Conversion')->label(fn ($row) => (($row->sale_leads - $row->created_sale_leads) / (($row->total_leads - $row->bad_leads - $row->manual_created) > 0 ? ($row->total_leads - $row->bad_leads - $row->manual_created) : 1)).' %')->footer(function ($rows) {
                $total = 0;
                foreach ($rows as $row) {
                    $total = $total + (($row->sale_leads - $row->created_sale_leads) / (($row->total_leads - $row->bad_leads - $row->manual_created) > 0 ? ($row->total_leads - $row->bad_leads - $row->manual_created) : 1));
                }

                return number_format((float) $total, 2, '.', '').' %';
            }),
        ];
    }

    public function builder(): Builder
    {
        return CarQuote::query()
          ->select(
              'users.id as advisorId',
              DB::raw('count(car_quote_request.id) as total_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 40 THEN 1 ELSE 0 END) as new_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 8 THEN 1 ELSE 0 END) as not_interested'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (2,24,25) THEN 1 ELSE 0 END) as in_progress'),
              DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" THEN 1 ELSE 0 END) as manual_created'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (9,35) THEN 1 ELSE 0 END) as bad_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) as sale_leads'),
              DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" and car_quote_request.quote_status_id = 15 THEN 1 ELSE 0 END) as created_sale_leads'),
          )
          ->join('users', 'users.id', 'car_quote_request.advisor_id')
          ->leftJoin('teams', function ($join) {
              $join->on('users.team_id', '=', 'teams.id');
              $join->on('users.sub_team_id', '=', 'teams.id');
          })
          ->whereNull('car_quote_request.renewal_import_code')
          ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
          ->orderBy('car_quote_request.quote_batch_id')->orderBy('users.email');
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
            SelectFilter::make('Ecommerce')
            ->setFilterPillTitle('ABC')
            ->options([
                '' => 'All',
                'yes' => 'Yes',
                'no' => 'No',
            ])->filter(function (Builder $builder, string $value) {
                $builder->where('car_quote_request.is_ecommerce', $value == 'no' ? false : true);
            }),
            MultiSelectFilter::make('Batch Number')
            ->options(
                QuoteBatches::query()
                    ->orderBy('id')
                    ->get()
                    ->keyBy('id')
                    ->map(fn ($batch) => $batch->name.'-('.$batch->start_date.' to '.$batch->end_date.')')
                    ->toArray(),
            )->filter(function (Builder $builder, $value) {
                $builder->whereIn('car_quote_request.quote_batch_id', $value);
            }),
            MultiSelectFilter::make('Teams')
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
                $builder->whereIn('teams.id', $value);
            }),
            MultiSelectFilter::make('Advisor Name')
            ->options(
                User::query()
                    ->orderBy('name')
                    ->where('is_active', 1)
                    ->get()
                    ->keyBy('id')
                    ->map(fn ($users) => $users->name)
                    ->toArray(),
            )->filter(function (Builder $builder, $value) {
                $builder->whereIn('car_quote_request.advisor_id', $value);
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
            MultiSelectFilter::make('Lead Source')
            ->options(
                LeadSource::query()
                    ->orderBy('name')
                    ->where('is_active', 1)
                    ->get()
                    ->keyBy('name')
                    ->map(fn ($users) => $users->name)
                    ->toArray(),
            )->filter(function (Builder $builder, $value) {
                $builder->whereIn('car_quote_request.source', $value);
            }),

        ];
    }
}
