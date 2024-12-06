<?php

namespace App\Http\Livewire;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
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

class AdvisorDistributionReportTable extends DataTableComponent
{
    use GetUserTreeTrait;

    public $url;
    public $tiers = [];
    public $teams = [];
    private $maxDays = 92;
    public function configure(): void
    {
        $this->setPrimaryKey('advisor.name')
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
            ->map(fn ($tier) => $tier->name)
            ->toArray();

        $this->teams = $this->getUserTeams($loginUserId)->keyBy('id')
            ->map(fn ($team) => $team->name)
            ->toArray();

        if (! $this->getAppliedFilterWithValue('created_at')) {
            $this->setFilter('created_at', now()->format('d-m-Y').'~'.now()->format('d-m-Y'));
        }
    }

    public function columns(): array
    {
        return [
            Column::make('Advisor Name', 'advisor.name')->searchable(),
            Column::make('Total Leads')->label(fn ($row) => ($row->total_leads))->footer(function ($rows) {
                return $rows->sum('total_leads');
            }),
            Column::make('Tier 0')->label(fn ($row) => $row->tier_0_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_0_lead_count');
            }),
            Column::make('Tier 1')->label(fn ($row) => $row->tier_1_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_1_lead_count');
            }),
            Column::make('Tier 2')->label(fn ($row) => $row->tier_2_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_2_lead_count');
            }),
            Column::make('Tier 3')->label(fn ($row) => $row->tier_3_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_3_lead_count');
            }),
            Column::make('Tier 4')->label(fn ($row) => $row->tier_4_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_4_lead_count');
            }),
            Column::make('Tier 5')->label(fn ($row) => $row->tier_5_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_5_lead_count');
            }),
            Column::make('Tier 6 NON-ECOM')->label(fn ($row) => $row->tier_6_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_6_lead_count');
            }),
            Column::make('Tier 6 ECOM')->label(fn ($row) => $row->tier_6_lead_count_e)->footer(function ($rows) {
                return $rows->sum('tier_6_lead_count_e');
            }),
            Column::make('Tier H')->label(fn ($row) => $row->tier_h_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_h_lead_count');
            }),
            Column::make('Tier L')->label(fn ($row) => $row->tier_l_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_l_lead_count');
            }),
            Column::make('Tier R')->label(fn ($row) => $row->tier_r_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_r_lead_count');
            }),
            Column::make('Tier TR ECOM')->label(fn ($row) => $row->tier_tr_lead_count_e)->footer(function ($rows) {
                return $rows->sum('tier_tr_lead_count_e');
            }),
            Column::make('Tier TR NON-ECOM')->label(fn ($row) => $row->tier_tr_lead_count)->footer(function ($rows) {
                return $rows->sum('tier_tr_lead_count');
            }),
            Column::make('Total Lead Cost')->label(fn ($row) => $row->total_lead_cost)->footer(function ($rows) {
                return $rows->sum('total_lead_cost');
            }),
        ];
    }

    public function builder(): Builder
    {
        $userIds = $this->walkTree(auth()->user()->id);
        info('user ids for advisor distribution report are : '.json_encode($userIds));

        $query = CarQuote::query()
            ->select(
                DB::raw('count(*) as total_leads'),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 0' THEN 1 ELSE 0 END) as tier_0_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 1' THEN 1 ELSE 0 END) as tier_1_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 2' THEN 1 ELSE 0 END) as tier_2_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 3' THEN 1 ELSE 0 END) as tier_3_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 4' THEN 1 ELSE 0 END) as tier_4_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 5' THEN 1 ELSE 0 END) as tier_5_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 6 (non ecom)' THEN 1 ELSE 0 END) as tier_6_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 6 (Ecom)' THEN 1 ELSE 0 END) as tier_6_lead_count_e"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier L' THEN 1 ELSE 0 END) as tier_l_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier H' THEN 1 ELSE 0 END) as tier_h_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier R' AND tiers.is_active = 1 THEN 1 ELSE 0 END) as tier_r_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier TR (Ecom)' AND tiers.is_active = 1 THEN 1 ELSE 0 END) as tier_tr_lead_count_e"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier TR (Non ecom)' AND tiers.is_active = 1 THEN 1 ELSE 0 END) as tier_tr_lead_count"),
                DB::raw('SUM(tiers.cost_per_lead) as total_lead_cost'),
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('user_team', 'user_team.user_id', 'users.id')
            ->join('teams', 'teams.id', 'user_team.team_id')
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('users.email')
            ->orderBy('users.name');
        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            $query->where('users.id', auth()->user()->id);
        } else {
            if (! auth()->user()->hasRole(RolesEnum::Admin)) {
                $userIds = $this->walkTree(auth()->user()->id);
                info('user ids for advisor conversion report are : '.json_encode($userIds));
                $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
            }
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
        ];
        if (auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::Admin, RolesEnum::Engineering])) {
            array_push(
                $filters,
                MultiSelectFilter::make('Teams')
                    ->options($this->teams)->config([
                        'placeholder' => 'SELECT ALL TEAMS',
                    ])
                    ->filter(function (Builder $builder, $value) {
                        $builder->whereIn('teams.id', $value);
                    }),
                MultiSelectFilter::make('Tiers')
                    ->options($this->tiers)->config([
                        'placeholder' => 'SELECT ALL TIERS',
                    ])
                    ->filter(function (Builder $builder, $value) {
                        $builder->whereIn('car_quote_request.tier_id', $value);
                    })
            );
        }

        return $filters;
    }
}
