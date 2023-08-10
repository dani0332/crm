<?php

namespace App\Http\Livewire;

use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\PaymentStatus;
use App\Models\Team;
use App\Models\Tier;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class LeadListReportTable extends DataTableComponent
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
                'after-pagination' => 'livewire.pagination',
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
            Column::make('Lead Code', 'uuid')->searchable(),
            Column::make('Name', 'first_name')->searchable(),
            Column::make('Lead source', 'source'),
            Column::make('Lead Status', 'quoteStatus.text'),
            Column::make('Payment Status', 'payment_status_id.text'),
            BooleanColumn::make('Is Ecommerce'),
            // Column::make('Duplicate', 'payment_status_id.text'),
            // Column::make('Call Count', 'payment_status_id.text'),
            Column::make('Assigned To', 'advisor.name'),
            // Column::make('Assigned From', 'payment_status_id.text'),
            Column::make('Created At', 'created_at'),
            Column::make('Last Modified', 'updated_at'),
            Column::make('Tier', 'tier.name'),
            Column::make('Received From Device', 'device'),
        ];
    }

    public function builder(): Builder
    {
        return CarQuote::query()->orderBy('car_quote_request.created_at', 'desc');
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
            SelectFilter::make('Lead Source')
                ->options(
                    LeadSource::query()
                        ->orderBy('name')
                        ->where('is_active', 1)
                        ->get()
                        ->keyBy('id')
                        ->map(fn ($source) => $source->name)
                        ->toArray(),
                )->filter(function (Builder $builder, $value) {
                    $builder->where('car_quote_request.source', $value);
                }),
            SelectFilter::make('Team')
                ->options(
                    Team::query()
                        ->orderBy('name')
                        ->get()
                        ->keyBy('id')
                        ->map(fn ($team) => $team->name)
                        ->toArray(),
                )->filter(function (Builder $builder, $value) {
                    $builder->whereIn('advisor.team_id', $value);
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
            SelectFilter::make('Payment Status')
                ->options(
                    PaymentStatus::query()
                        ->orderBy('text')
                        ->where('is_active', 1)
                        ->get()
                        ->keyBy('id')
                        ->map(fn ($paymentStatus) => $paymentStatus->text)
                        ->toArray(),
                )->filter(function (Builder $builder, $value) {
                    $builder->where('car_quote_request.payment_status_id', $value);
                }),
        ];
    }
}
