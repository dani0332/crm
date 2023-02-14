<?php

namespace App\Http\Livewire;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\QuoteBatches;
use App\Models\Tier;
use App\Models\User;
use App\Services\ApplicationStorageService;
use App\Traits\GetUserTreeTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class AdvisorConversionReportTable extends DataTableComponent
{
    use GetUserTreeTrait;

    public $url;
    public $tiers = [];
    public $batches = [];
    public $leadSources = [];
    private $maxDays = 92;
    private $advisors = [];
    private $teams = [];

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
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $this->teams = collect(DB::select("
            select
            CONCAT(teams.`name`,' ', CASE WHEN `type` = 2 THEN '- Team' ELSE '- SubTeam' END) as name,
            teams.id
            from teams
            where id in (select team_id from user_team where user_id = '".$loginUserId."' ) OR id = (select sub_team_id from users where id =  '".$loginUserId."');"))
            ->keyBy('id')
            ->map(fn ($Teams) => $Teams->name)
            ->toArray();
        $this->tiers = Tier::query()
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $this->batches = QuoteBatches::query()
            ->orderBy('id')
            ->get()
            ->keyBy('id')
            ->map(fn ($batch) => $batch->name.'-('.$batch->start_date.' to '.$batch->end_date.')')
            ->toArray();

        $this->leadSources = CarQuote::query()
            ->select('source as name')
            ->distinct()
            ->whereNotNull('source')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        if (! $this->getAppliedFilterWithValue('created_at')) {
            $this->setFilter('created_at', now()->subDays($this->maxDays)->format('d-m-Y').'~'.now()->format('d-m-Y'));
        }
    }

    public function columns(): array
    {

        return [
            Column::make('Batch Number', 'batch.name')->footer(function () {
                return  'Total';
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
                    fn ($row, Column $column) => '<a '.($row->total_leads > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"'). ' x-on:click="window.livewire.emit(`tableModal`, ' . $row . ', `total_leads`)" class="text-sky-700 cursor-pointer">'.$row->total_leads.'</a>'
                )->html()->footer(function ($rows) {
                    return $rows->sum('total_leads');
                }),
            Column::make('New Leads')->label(
                fn ($row, Column $column) => '<a '.($row->new_leads > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').' x-on:click="window.livewire.emit(`tableModal`, ' . $row . ', `new_leads`)" class="text-sky-700 cursor-pointer">'.$row->new_leads.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('new_leads');
            }),
            Column::make('Not Interested')->label(
                fn ($row, Column $column) => '<a '.($row->not_interested > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').' x-on:click="window.livewire.emit(`tableModal`, ' . $row . ', `not_interested`)" class="text-sky-700 cursor-pointer">'.$row->not_interested.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('not_interested');
            }),
            Column::make('In Progress')->label(
                fn ($row, Column $column) => '<a '.($row->in_progress > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').' x-on:click="window.livewire.emit(`tableModal`, ' . $row . ', `in_progress`)" class="text-sky-700 cursor-pointer">'.$row->in_progress.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('in_progress');
            }),

            Column::make('Bad Leads')->label(
                fn ($row, Column $column) => '<a '.($row->bad_leads > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, ' . $row . ', `bad_leads`)" class="text-sky-700 cursor-pointer">'.$row->bad_leads.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('bad_leads');
            }),
            Column::make('Sale Leads')->label(
                fn ($row, Column $column) => '<a '.($row->sale_leads > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, ' . $row . ', `sale_leads`)" class="text-sky-700 cursor-pointer">'.$row->sale_leads.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('sale_leads');
            }),
            Column::make('AFIA Renewals')->label(
                fn ($row, Column $column) => '<a '.($row->afia_renewals_count > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, ' . $row . ', `afia_renewals_count`)" class="text-sky-700 cursor-pointer">'.$row->afia_renewals_count.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('afia_renewals_count');
            }),
            Column::make('Manual Created')->label(
                fn ($row, Column $column) => '<a '.($row->manual_created > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, ' . $row . ', `manual_created`)" class="text-sky-700 cursor-pointer">'.$row->manual_created.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('manual_created');
            }),
            Column::make('Others')->label(
                fn ($row, Column $column) => '<a '.($row->others > 0 ? 'style="text-decoration:underline;"' : 'style="color:black;"').'  x-on:click="window.livewire.emit(`tableModal`, ' . $row . ', `others`)" class="text-sky-700 cursor-pointer">'.$row->others.'</a>'
            )->html()->footer(function ($rows) {
                return $rows->sum('others');
            }),
            Column::make('Gross Conversion')->label(fn ($row) => ($row->total_leads - $row->manual_created) > 0 ? number_format((($row->sale_leads - $row->created_sale_leads) / (($row->total_leads - $row->manual_created))), 2, '.', '').' %' : 'NaN')->footer(function ($rows) {
                $total = 0;
                foreach ($rows as $row) {
                    if (($row->total_leads - $row->manual_created) > 0) {
                        $total = $total + (($row->sale_leads - $row->created_sale_leads) / (($row->total_leads - $row->manual_created)));
                    }
                }

                return number_format($total, 2, '.', '').' %';
            }),
            Column::make('Net Conversion')->label(fn ($row) => ($row->total_leads - $row->bad_leads - $row->manual_created) > 0 ? number_format((($row->sale_leads - $row->created_sale_leads) / (($row->total_leads - $row->bad_leads - $row->manual_created))), 2, '.', '').' %' : 'NaN')->footer(function ($rows) {
                $total = 0;
                foreach ($rows as $row) {
                    if (($row->total_leads - $row->bad_leads - $row->manual_created) > 0) {
                        $total = $total + (($row->sale_leads - $row->created_sale_leads) / (($row->total_leads - $row->bad_leads - $row->manual_created)));
                    }
                }

                return number_format($total, 2, '.', '').' %';
            }),
        ];
    }

    public function builder(): Builder
    {
        $userIds = $this->walkTree(auth()->user()->id);
        info('user ids for advisor conversion report are : '.json_encode($userIds));
        $query = CarQuote::query()
        ->select(
            'users.id as advisorId',
            DB::raw('count(car_quote_request.id) as total_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN 1 ELSE 0 END) as new_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.') THEN 1 ELSE 0 END) as not_interested'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.') THEN 1 ELSE 0 END) as in_progress'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') THEN 1 ELSE 0 END) as bad_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::TransactionApproved.' THEN 1 ELSE 0 END) as sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" and car_quote_request.quote_status_id = '.QuoteStatusEnum::TransactionApproved.' THEN 1 ELSE 0 END) as created_sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::AfiaRenewal.' THEN 1 ELSE 0 END) as afia_renewals_count'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id not in (
                '.QuoteStatusEnum::NewLead.','.QuoteStatusEnum::PriceTooHigh.','.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.','.QuoteStatusEnum::NotInterested.',
                '.QuoteStatusEnum::NotEligibleForInsurance.','.QuoteStatusEnum::NotLookingForMotorInsurance.','.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::NotContactablePe.',
                '.QuoteStatusEnum::FollowupCall.','.QuoteStatusEnum::Interested.','.QuoteStatusEnum::NoAnswer.','.QuoteStatusEnum::Quoted.','.QuoteStatusEnum::Duplicate.',
                '.QuoteStatusEnum::Fake.','.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::AfiaRenewal.') THEN 1 ELSE 0 END) as others'),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        ->join('user_team', 'user_team.user_id', 'users.id')
        ->join('teams', 'teams.id', 'user_team.team_id')
        ->whereNull('car_quote_request.renewal_import_code')
        ->whereIn('car_quote_request.advisor_id', $userIds)
        ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
        ->orderBy('car_quote_request.quote_batch_id')->orderBy('users.email');

        return $query;
    }

    public function filters(): array
    {
        $filters = [
            TextFilter::make('Created Date', 'created_at')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => $this->maxDays,
                ])
                ->filter(function (Builder $builder, string $value) {
                    $dates = explode('~', $value);
                    $dates[0] = Carbon::parse($dates[0])->startOfDay()->format('Y-m-d H:i:s');
                    $dates[1] = Carbon::parse($dates[1])->endOfDay()->format('Y-m-d H:i:s');
                    $builder->whereBetween('car_quote_request.created_at', $dates);
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
            array_push($filters, SelectFilter::make('Exclude Created Leads')
            ->options([
                'no' => 'No',
                'yes' => 'Yes',
            ])->filter(function (Builder $builder, string $value) {
                if($value == 'yes'){
                    $builder->where('car_quote_request.source', '!=', LeadSourceEnum::IMCRM);
                }
            }));
            array_push($filters, MultiSelectFilter::make('Lead Source')
            ->options($this->leadSources)->filter(function (Builder $builder, $value) {
                $builder->whereIn('car_quote_request.source', $value);
            }));

            array_push($filters, MultiSelectFilter::make('Teams')
            ->options($this->teams)->filter(function (Builder $builder, $value) {
                $builder->whereIn('teams.id', $value);
            }));

            array_push($filters, MultiSelectFilter::make('Advisors')
            ->options($this->advisors)->filter(function (Builder $builder, $value) {
                $builder->whereIn('car_quote_request.advisor_id', $value);
            }));
        }

        return $filters;
    }
}
