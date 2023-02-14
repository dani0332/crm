<?php

namespace App\Http\Livewire;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Models\CarQuote;
use App\Models\Tier;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class LeadDistributionReportTable extends DataTableComponent
{
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
            $this->setFilter('created_at', now()->subDays($this->maxDays)->format('d-m-Y').'~'.now()->format('d-m-Y'));
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
                DB::raw('SUM(CASE WHEN car_quote_request.source not in ("'.LeadSourceEnum::RENEWAL_UPLOAD.'","'.LeadSourceEnum::IMCRM.'","'.LeadSourceEnum::TPL_RENEWALS.'") THEN 1 ELSE 0 END) as received_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as lead_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id is null THEN 1 ELSE 0 END) as unassigned_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.auto_assigned = 1 THEN 1 ELSE 0 END) as auto_assigned'),
                DB::raw('SUM(CASE WHEN car_quote_request.auto_assigned = 0 THEN 1 ELSE 0 END) as manually_assigned'),
                DB::raw('count(car_quote_request.id) as total_leads'),
            )
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->groupBy('tiers.name');
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Created Date', 'created_at')
                ->config([
                    'placeholder' => 'Select Start & End Date',
                    'range' => true,
                    'max_days' => $this->maxDays,
                ])
                ->filter(function (Builder $builder, string $value) {
                    $dates = explode('~', $value);
                    $dates[0] = Carbon::parse($dates[0])->format('Y-m-d');
                    $dates[1] = Carbon::parse($dates[1])->format('Y-m-d');
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
