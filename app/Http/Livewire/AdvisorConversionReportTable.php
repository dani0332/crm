<?php

namespace App\Http\Livewire;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\QuoteBatches;
use App\Models\Tier;
use App\Models\User;
use App\Services\ApplicationStorageService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class AdvisorConversionReportTable extends DataTableComponent
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public $url;
    public $tiers = [];
    public $batches = [];
    public $leadSources = [];
    public $maxDays = 92;
    public $advisors = [];
    public $teams = [];
    public $createdAtFilter;
    public $ecommerceFilter;
    public $excludeCreatedLeadsFilter;
    public $batchNumberFilter;
    public $tiersFilter;
    public $leadSourceFilter;
    public $teamsFilter;
    public $advisorsFilter;
    protected $listeners = [
        'tableModal' => 'showTableModal',
    ];

    public function showTableModal($data, $leadType)
    {
        $this->emitTo('table-modal', 'show', $data, $this->getAppliedFilters(), $leadType);
    }

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

    public function mount()
    {
        $loginUserId = auth()->user()->id;
        $userIds = $this->walkTree($loginUserId);
        $this->maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $this->advisors = User::whereIn('id', $userIds)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $this->teams = $this->getUserTeams($loginUserId)->keyBy('id')
            ->map(fn ($Teams) => $Teams->name)
            ->toArray();
        $this->tiers = Tier::query()
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $this->batches = QuoteBatches::query()
            ->select('name', 'start_date', 'end_date', 'id')
            ->orderBy('id')
            ->get()
            ->keyBy('id')
            ->map(fn ($batch) => $batch->name.'-('.$batch->start_date.' to '.$batch->end_date.')')
            ->toArray();

        $this->leadSources = LeadSource::query()
            ->select('name')
            ->where('is_active', 1)->where('is_applicable_for_rules', 0)
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        if (! $this->getAppliedFilterWithValue('created_at')) {
            $this->setFilter('created_at', now()->format('d-m-Y').'~'.now()->format('d-m-Y'));
        }
    }

    public function columns(): array
    {
        return [
            Column::make('Batch Number', 'batch.name')->footer(function () {
                return 'Total';
            }),
            Column::make('Start Date', 'batch.start_date')->format(
                fn ($value) => $value ? Carbon::parse($value)->format('d-m-Y') : null
            ),
            Column::make('Stop Date', 'batch.end_date')->format(
                fn ($value) => $value ? Carbon::parse($value)->format('d-m-Y') : null
            ),
            Column::make('Advisor Name', 'advisor.name')->searchable(),
            Column::make('Total Leads')
                ->label(
                    fn ($row, Column $column) => '<a '.($row->total_leads > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').' x-on:click="window.livewire.emit(`tableModal`, '.$row.', `total_leads`)" class="text-sky-700 cursor-pointer">'.$row->total_leads.'</a>'
                )->html()->footer(function ($rows) {
                    return $rows->sum('total_leads');
                }),
            Column::make('New Leads')->label(
                fn ($row, Column $column) => '<a '.($row->new_leads > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').' x-on:click="window.livewire.emit(`tableModal`, '.$row.', `new_leads`)" class="text-sky-700 cursor-pointer">'.$row->new_leads.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('new_leads');
            }),
            Column::make('Not Interested')->label(
                fn ($row, Column $column) => '<a '.($row->not_interested > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').' x-on:click="window.livewire.emit(`tableModal`, '.$row.', `not_interested`)" class="text-sky-700 cursor-pointer">'.$row->not_interested.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('not_interested');
            }),
            Column::make('In Progress')->label(
                fn ($row, Column $column) => '<a '.($row->in_progress > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').' x-on:click="window.livewire.emit(`tableModal`, '.$row.', `in_progress`)" class="text-sky-700 cursor-pointer">'.$row->in_progress.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('in_progress');
            }),

            Column::make('Bad Leads')->label(
                fn ($row, Column $column) => '<a '.($row->bad_leads > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, '.$row.', `bad_leads`)" class="text-sky-700 cursor-pointer">'.$row->bad_leads.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('bad_leads');
            }),
            Column::make('Sale Leads')->label(
                fn ($row, Column $column) => '<a '.($row->sale_leads > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, '.$row.', `sale_leads`)" class="text-sky-700 cursor-pointer">'.$row->sale_leads.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('sale_leads');
            }),
            Column::make('Created Sale Leads')->label(
                fn ($row, Column $column) => '<a '.($row->created_sale_leads > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, '.$row.', `created_sale_leads`)" class="text-sky-700 cursor-pointer">'.$row->created_sale_leads.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('created_sale_leads');
            }),
            Column::make('IM Renewals')->label(
                fn ($row, Column $column) => '<a '.($row->afia_renewals_count > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, '.$row.', `afia_renewals_count`)" class="text-sky-700 cursor-pointer">'.$row->afia_renewals_count.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('afia_renewals_count');
            }),
            Column::make('Manual Created')->label(
                fn ($row, Column $column) => '<a '.($row->manual_created > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, '.$row.', `manual_created`)" class="text-sky-700 cursor-pointer">'.$row->manual_created.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('manual_created');
            }),
            Column::make('Gross Conversion')->label(fn ($row) => $this->calculateGrossConversion($row))->footer(function ($rows) {
                return $this->calculateTotalGrossConversion($rows);
            }),
            Column::make('Net Conversion')->label(fn ($row) => $this->calculateNetConversion($row))->footer(function ($rows) {
                return $this->calculateTotalNetConversion($rows);
            }),
        ];
    }

    public function builder(): Builder
    {
        $query = CarQuote::query()
            ->select(
                'users.id as advisorId',
                DB::raw('count(car_quote_request.id) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.') THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.') THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id  in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.') THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" and car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.') THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END) as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
            ->orderBy('car_quote_request.quote_batch_id')->orderBy('users.email');

        if (! auth()->user()->hasRole(RolesEnum::Admin)) {
            $userIds = $this->walkTree(auth()->user()->id);
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        }

        return $query;
    }

    public function filters(): array
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $filters = [
            TextFilter::make('Advisor Assigned Date', 'created_at')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => $this->maxDays,
                ])
                ->filter(function (Builder $builder, string $value) use ($dateFormat) {
                    $dates = explode('~', $value);
                    $dates[0] = Carbon::parse($dates[0])->startOfDay()->format($dateFormat);
                    $dates[1] = Carbon::parse($dates[1])->endOfDay()->format($dateFormat);
                    $builder->whereBetween('car_quote_request_detail.advisor_assigned_date', $dates);
                }),
            SelectFilter::make('Ecommerce')
                ->options([
                    '' => 'All',
                    'yes' => 'Yes',
                    'no' => 'No',
                ])->filter(function (Builder $builder, string $value) {
                    $builder->where('car_quote_request.is_ecommerce', $value == 'no' ? false : true);
                }),
            MultiSelectFilter::make('Batch Number')
                ->options($this->batches)->config([
                    'max' => 12,
                ])->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.quote_batch_id', $value);
                }),
            MultiSelectFilter::make('Tiers')->config([
                'placeholder' => 'SELECT ALL TIERS',
            ])
                ->options($this->tiers)->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.tier_id', $value);
                }),

        ];
        if (! auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            array_push($filters, MultiSelectFilter::make('Lead Source')
                ->options($this->leadSources)->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.source', $value);
                }));

            array_push($filters, MultiSelectFilter::make('Teams')
                ->config([
                    'placeholder' => 'SELECT ALL TEAMS',
                ])
                ->options($this->teams)->filter(function (Builder $builder, $value) {
                    $builder->whereIn('users.id', function ($query) use ($value) {
                        $query->distinct()
                            ->select('users.id')
                            ->from('users')
                            ->join('user_team', 'user_team.user_id', 'users.id')
                            ->join('teams', 'teams.id', 'user_team.team_id')
                            ->whereIn('teams.id', $value);
                    });
                }));

            array_push($filters, MultiSelectFilter::make('Advisors')
                ->config([
                    'placeholder' => 'SELECT ALL ADVISORS',
                ])
                ->options($this->advisors)->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.advisor_id', $value);
                }));
        }

        return $filters;
    }

    private function calculateTotalGrossConversion($rows)
    {
        $totalLeads = 0;
        $manualCreated = 0;
        $saleLeads = 0;
        $createdSaleLeads = 0;
        foreach ($rows as $row) {
            $totalLeads += $row->total_leads;
            $manualCreated += $row->manual_created;
            $saleLeads += $row->sale_leads;
            $createdSaleLeads += $row->created_sale_leads;
        }
        $numerator = $saleLeads - $createdSaleLeads;
        $denominator = $totalLeads - $manualCreated;

        return $denominator > 0 ? number_format($numerator / $denominator * 100, 2, '.', '').' %' : 'NaN';
    }

    private function calculateGrossConversion($row)
    {
        $totalLeads = (int) $row->total_leads;
        $manualCreated = (int) $row->manual_created;
        $saleLeads = (int) $row->sale_leads;
        $createdSaleLeads = (int) $row->created_sale_leads;
        $numerator = $saleLeads - $createdSaleLeads;
        $denominator = $totalLeads - $manualCreated;
        if ($denominator > 0) {
            return number_format(($numerator / $denominator) * 100, 2, '.', '').' %';
        } else {
            return 'NaN';
        }
    }

    private function calculateTotalNetConversion($rows)
    {
        $totalLeads = 0;
        $manualCreated = 0;
        $badLeads = 0;
        $manualCreatedBadLeads = 0;
        $saleLeads = 0;
        $createdSaleLeads = 0;
        foreach ($rows as $row) {
            $totalLeads += $row->total_leads;
            $manualCreated += $row->manual_created;
            $saleLeads += $row->sale_leads;
            $createdSaleLeads += $row->created_sale_leads;
            $badLeads += $row->bad_leads;
            $manualCreatedBadLeads += $row->manual_created_bad_leads;
        }
        $numerator = $saleLeads - $createdSaleLeads;
        $denominator = ($totalLeads - $manualCreated) - ($badLeads - $manualCreatedBadLeads);

        return $denominator > 0 ? number_format($numerator / $denominator * 100, 2, '.', '').' %' : 'NaN';
    }

    private function calculateNetConversion($row)
    {
        $totalLeads = (int) $row->total_leads;
        $manualCreated = (int) $row->manual_created;
        $badLeads = (int) $row->bad_leads;
        $manualCreatedBadLeads = (int) $row->manual_created_bad_leads;
        $saleLeads = (int) $row->sale_leads;
        $createdSaleLeads = (int) $row->created_sale_leads;
        $numerator = $saleLeads - $createdSaleLeads;
        $denominator = ($totalLeads - $manualCreated) - ($badLeads - $manualCreatedBadLeads);

        return $denominator > 0 ? number_format($numerator / $denominator * 100, 2, '.', '').' %' : 'NaN';
    }
}
