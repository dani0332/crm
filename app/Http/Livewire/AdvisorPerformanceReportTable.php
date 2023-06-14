<?php

namespace App\Http\Livewire;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\Tier;
use App\Services\ApplicationStorageService;
use App\Traits\GetUserTreeTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class AdvisorPerformanceReportTable extends DataTableComponent
{
    use GetUserTreeTrait;

    public $url;
    public $tiers = [];
    public $teams = [];
    public $leadSources = [];
    private $maxDays = 92;
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
        $this->maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $this->tiers = Tier::query()
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $this->teams = $this->getUserTeams($loginUserId)->keyBy('id')
            ->map(fn ($Teams) => $Teams->name)
            ->toArray();

        $this->leadSources = LeadSource::query()
            ->select('name')
            ->distinct()
            ->where('is_active', 1)->where('is_applicable_for_rules', 0)
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        if (! $this->getAppliedFilterWithValue('advisor_assigned_date')) {
            $this->setFilter('advisor_assigned_date', now()->format('d-m-Y').'~'.now()->format('d-m-Y'));
        }
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
        $query = CarQuote::query()
            ->select(
                DB::raw('count(DISTINCT car_quote_request.id) as total_leads'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as new_leads'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.auto_assigned = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as auto_assigned'),
                DB::raw('CAST(SUM(CASE WHEN (car_quote_request.auto_assigned = 0 and source != "'.LeadSourceEnum::IMCRM.'" ) THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as manually_assigned'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.') THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as not_interested'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.') THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as in_progress'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as manual_created'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as bad_leads'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.') THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as sale_leads'),
                DB::raw('IFNULL(CAST(SUM(quote_view_count.visit_count) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED), 0) as view_count'),
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->leftJoin('quote_view_count', 'quote_view_count.quote_id', 'car_quote_request.id')
            ->join('user_team', 'user_team.user_id', 'users.id')
            ->join('teams', 'teams.id', 'user_team.team_id')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('car_quote_request.advisor_id')
            ->orderBy('users.email');

        if (! auth()->user()->hasRole(RolesEnum::LeadPool)) {
            $userIds = $this->walkTree(auth()->user()->id);
            info('user ids for advisor performance report are : '.json_encode($userIds));
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        }

        return $query;
    }

    public function filters(): array
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        return [
            TextFilter::make('Advisor Assigned', 'advisor_assigned_date')
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
            MultiSelectFilter::make('Teams')->config([
                'placeholder' => 'SELECT ALL TEAMS',
            ])
                ->options($this->teams)->filter(function (Builder $builder, $value) {
                    $builder->whereIn('teams.id', $value);
                }),
            MultiSelectFilter::make('Tiers')->config([
                'placeholder' => 'SELECT ALL TIERS',
            ])
                ->options($this->tiers)->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.tier_id', $value);
                }),
            MultiSelectFilter::make('Lead Source')
                ->options($this->leadSources)->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.source', $value);
                }),

        ];
    }
}
