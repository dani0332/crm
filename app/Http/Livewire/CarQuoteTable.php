<?php

namespace App\Http\Livewire;

use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Models\PaymentStatus;
use App\Models\QuoteStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class CarQuoteTable extends DataTableComponent
{
    public function index()
    {
        return view('livewire.quote.car');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('created_at', 'desc')
            ->setAdditionalSelects(['car_quote_request.uuid as uuid'])
            ->setSearchDisabled()
            ->setPerPageVisibilityDisabled()
            ->setFilterLayoutSlideDown();
        // disabled pagination count & pagination view
        $this->setPaginationVisibilityDisabled();
        $this->setConfigurableAreas([
            'after-pagination' => 'livewire.pagination',
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make('CDB ID', 'code')
                ->format(
                    function ($value, $row, Column $column) {
                        return '<a href="'.url('/quotes').'/car/'.$row->uuid.'" target="_blank" title="View" class="text-sky-700">'.$value.'</a>';
                    }
                )
                ->html(),
            Column::make('Advisor', 'advisor.name'),
            Column::make('First Name'),
            Column::make('Last Name'),
            Column::make('Currently Insured With'),
            Column::make('Lead Status', 'quoteStatus.text'),
            Column::make('Vehicle Type', 'vehicle_type_id'),
            Column::make('Updated at'),
            Column::make('Created at'),
            Column::make('Home Country Driving License Held For', 'uaeLicenseHeldFor.text'),
            Column::make('Seat Capacity'),
            Column::make('Cylinder'),
            BooleanColumn::make('Can Provide No-claims Letter From Previous Insurers', 'has_ncd_supporting_documents'),
            Column::make('Source'),
            BooleanColumn::make('Ecommerce', 'is_ecommerce'),
            Column::make('Payment Status', 'paymentStatus.text'),
            Column::make('Premium'),
            Column::make('Quote Link'),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Created Date', 'created_at')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => 365,
                ])
                ->filter(function (Builder $builder, string $value) {
                    $value = explode(' - ', $value);
                    $builder->whereBetween('car_quote_request.created_at', [$value[0], $value[1]]);
                }),

            TextFilter::make('Assigned Date', 'assigned_date')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => 365,
                ])
                ->filter(function (Builder $builder, string $value) {
                    $value = explode(' - ', $value);
                    $builder->whereBetween('car_quote_request.updated_at', [$value[0], $value[1]]);
                }),

            TextFilter::make('CDB ID', 'code')
                ->config([
                    'placeholder' => 'Search by CDB ID',
                    'maxlength' => '25',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.code', $value);
                }),

            MultiSelectFilter::make('Advisor')
                ->options(
                    User::query()
                        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->whereIn('roles.name', [RolesEnum::CarAdvisor, RolesEnum::CarNewBusinessAdvisor, RolesEnum::CarRenewalAdvisor])
                        ->select('users.id', DB::raw("CONCAT(users.name,' - ',roles.name) as name"))
                        ->orderBy('roles.name')
                        ->get()
                        ->keyBy('id')
                        ->map(fn ($users) => $users->name)
                        ->toArray(),
                )->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.advisor_id', $value);
                }),

            SelectFilter::make('Currently Insured With')
                ->options(
                    ['' => 'Select Insurance Provider'] +
                        InsuranceProvider::query()
                        ->orderBy('text')
                        ->get()
                        ->pluck('text', 'text')
                        ->toArray(),
                )->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.currently_insured_with', $value);
                }),

            MultiSelectFilter::make('Lead Status')
                ->options(
                    QuoteStatus::query()
                        ->whereNotIn('id', [6, 1, 3, 7, 32, 34, 4])
                        ->where('is_active', 1)
                        ->orderBy('sort_order')
                        ->get()
                        ->keyBy('id')
                        ->map(fn ($status) => $status->text)
                        ->toArray(),
                )->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.quote_status_id', $value);
                }),

            MultiSelectFilter::make('Payment Status')
                ->options(
                    PaymentStatus::query()
                        ->orderBy('text')
                        ->where('is_active', 1)
                        ->get()
                        ->keyBy('id')
                        ->map(fn ($status) => $status->text)
                        ->toArray(),
                )->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.payment_status_id', $value);
                }),

            SelectFilter::make('Is Ecommerce')
                ->options([
                    '' => 'All',
                    '1' => 'Yes',
                    '0' => 'No',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '1') {
                        $builder->where('is_ecommerce', true);
                    } elseif ($value === '0') {
                        $builder->where('is_ecommerce', false);
                    }
                }),

            TextFilter::make('First Name')
                ->config([
                    'placeholder' => 'Search by First Name',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.first_name', $value);
                }),

            TextFilter::make('Last Name')
                ->config([
                    'placeholder' => 'Search by Last Name',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.last_name', $value);
                }),

            TextFilter::make('Email')
                ->config([
                    'placeholder' => 'Search by Email Address',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.email', $value);
                }),

            TextFilter::make('Phone Number')
                ->config([
                    'placeholder' => 'Search by Phone Number',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.mobile_no', $value);
                }),

        ];
    }

    public function builder(): Builder
    {
        return CarQuote::query()
            ->where('quote_status.id', '!=', 9);
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
}
