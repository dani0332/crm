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

class LeadDistributionReportTable extends DataTableComponent
{
    use GetUserTreeTrait;

    public $url;
    public $tiers = [];
    private $maxDays = 92;
    public function configure(): void
    {
        $this->setPrimaryKey('tier.name')
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

    public function mount()
    {
        $this->maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $this->tiers = Tier::query()
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        if (! $this->getAppliedFilterWithValue('created_at')) {
            $this->setFilter('created_at', now()->format('d-m-Y').'~'.now()->format('d-m-Y'));
        }
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
            Column::make('Total Leads')->label(fn ($row) => $row->total_leads)->footer(function ($rows) {
                return $rows->sum('total_leads');
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
        $query = CarQuote::leftJoin('tiers', 'tiers.id', '=', 'car_quote_request.tier_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->select(DB::raw('(

            (SUM(CASE WHEN car_quote_request.assignment_type in (1,2) AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END)
            + SUM(CASE WHEN car_quote_request.assignment_type in (3,4) AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END)
            + SUM(CASE WHEN car_quote_request.advisor_id IS NULL THEN 1 ELSE 0 END))) AS received_leads,

            SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) AS lead_created, COUNT(*) AS total_leads,

            SUM(CASE WHEN car_quote_request.assignment_type in (1,2) AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS auto_assigned,

            SUM(CASE WHEN car_quote_request.assignment_type in (3,4) AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS manually_assigned,

            SUM(CASE WHEN car_quote_request.advisor_id IS NULL THEN 1 ELSE 0 END) AS unassigned_leads'), 'tiers.name AS tier_name')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('tiers.name');

        if (! auth()->user()->hasRole(RolesEnum::LeadPool)) {
            $userIds = $this->walkTree(auth()->user()->id);
            info('user ids for lead distribution report are : '.json_encode($userIds));
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        }

        return $query->orderBy('tiers.id');
    }

    public function filters(): array
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        return [
            TextFilter::make('Created Date', 'created_at')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => $this->maxDays,
                ])
                ->filter(function (Builder $builder, string $value) use ($dateFormat) {
                    $dates = explode('~', $value);
                    info('lead dates : '.json_encode($dates));
                    $dates[0] = Carbon::parse($dates[0])->startOfDay()->format($dateFormat);
                    $dates[1] = Carbon::parse($dates[1])->endOfDay()->format($dateFormat);
                    $builder->whereBetween('car_quote_request.created_at', $dates);
                }),
            MultiSelectFilter::make('Tiers')
                ->options($this->tiers)
                ->filter(function (Builder $builder, $value) {
                    $builder->whereIn('car_quote_request.tier_id', $value);
                }),

        ];
    }
}
