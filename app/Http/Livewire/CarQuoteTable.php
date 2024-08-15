<?php

namespace App\Http\Livewire;

use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Models\PaymentStatus;
use App\Models\QuoteStatus;
use App\Models\User;
use App\Models\VehicleType;
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
            ->setColumnSelectDisabled()
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
            Column::make('Ref-ID', 'code')
                ->format(
                    function ($value, $row, Column $column) {
                        return '<a href="'.url('/quotes').'/car/'.$row->uuid.'" target="_blank" title="View Detail" class="text-sky-700">'.$value.'</a>';
                    }
                )
                ->html(),
            Column::make('Renewal Batch #', 'renewal_batch'),
            Column::make('Advisor', 'advisor.name'),
            Column::make('First Name'),
            Column::make('Last Name'),
            Column::make('Date of Birth', 'dob')
                ->format(
                    fn ($value) => $value ? date_format($value, 'Y-m-d') : null
                ),
            Column::make('Nationality', 'nationality.text'),
            Column::make('UAE Licence Held For', 'uaeLicenseHeldFor.text'),
            Column::make('Claim History', 'claimHistory.text'),
            Column::make('Policy Number'),
            Column::make('Currently Insured With'),
            Column::make('Type Of Car Insurance', 'carTypeInsurance.text'),
            Column::make('Lead Status', 'quoteStatus.text'),
            Column::make('Car Make', 'carMake.text'),
            Column::make('Car Model', 'carModel.text'),
            Column::make('Car Model Year', 'year_of_manufacture'),
            Column::make('Car Value'),
            Column::make('Vehicle Type', 'vehicleType.text'),
            Column::make('First Registration Date', 'year_of_first_registration'),
            BooleanColumn::make('Is Gcc Standard'),
            BooleanColumn::make('Vehicle Modified', 'is_modified'),
            Column::make('Payment Status', 'paymentStatus.text'),
            Column::make('Premium'),
            Column::make('Last Modified Date', 'updated_at'),
            Column::make('Created at'),
            Column::make('Lost Reason', 'carQuoteRequestDetail.lostReason.text'),
            Column::make('Quote Link'),
            Column::make('Source'),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Ref-ID', 'code')
                ->config([
                    'placeholder' => 'Search by Ref-ID',
                    'maxlength' => '25',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.code', $value);
                }),

            TextFilter::make('Renewal Batch #', 'renewal_batch_number')
                ->config([
                    'placeholder' => 'Search by Renewal Batch #',
                    'maxlength' => '25',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.renewal_batch', $value);
                }),

            TextFilter::make('Policy Expiry Date', 'policy_expiry_date')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => 365,
                ])
                ->filter(function (Builder $builder, string $value) {
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})~(\d{4}-\d{2}-\d{2})$/', $value, $matches)) {
                        $builder->whereBetween('car_quote_request.previous_policy_expiry_date', [$matches[1], $matches[2]]);
                    }
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

            TextFilter::make('Created Date', 'created_at')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => 365,
                ])
                ->filter(function (Builder $builder, string $value) {
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})~(\d{4}-\d{2}-\d{2})$/', $value, $matches)) {
                        $builder->whereBetween('car_quote_request.created_at', [$matches[1], $matches[2]]);
                    }
                }),

            TextFilter::make('Assigned Date', 'assigned_date')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => 365,
                ])
                ->filter(function (Builder $builder, string $value) {
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})~(\d{4}-\d{2}-\d{2})$/', $value, $matches)) {
                        $builder

                            ->whereBetween('car_quote_request_detail.advisor_assigned_date', [$matches[1], $matches[2]]);
                    }
                }),

            SelectFilter::make('Currently Insured With')
                ->options(
                    ['' => 'Select Insurance Provider'] +
                        InsuranceProvider::query()
                            ->select('id', 'text')
                            ->orderBy('text')
                            ->get()
                            ->pluck('text', 'text')
                            ->toArray(),
                )->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.currently_insured_with', $value);
                }),

            SelectFilter::make('Vehicle Type')
                ->options(
                    ['' => 'Select Vehicle Type'] +
                        VehicleType::query()
                            ->where('is_active', true)
                            ->select('id', 'text')
                            ->orderBy('text')
                            ->get()
                            ->pluck('text', 'id')
                            ->toArray(),
                )->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.vehicle_type_id', $value);
                }),

            MultiSelectFilter::make('Lead Status')
                ->options(
                    QuoteStatus::query()
                        ->whereNotIn('id', [
                            QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::Draft, QuoteStatusEnum::Cancelled, QuoteStatusEnum::AMLScreeningFailed,
                            QuoteStatusEnum::TransactionDeclined, QuoteStatusEnum::PolicyInvoiced, QuoteStatusEnum::Issued,
                        ])
                        ->where('is_active', true)
                        ->orderBy('sort_order', 'asc')->get()
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

            TextFilter::make('Previous Policy Number')
                ->config([
                    'placeholder' => 'Search by Previous Policy Number',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.previous_quote_policy_number', 'like', '%'.$value.'%');
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
