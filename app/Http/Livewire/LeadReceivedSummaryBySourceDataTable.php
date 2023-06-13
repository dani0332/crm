<?php

namespace App\Http\Livewire;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CarQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class LeadReceivedSummaryBySourceDataTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setColumnSelectDisabled()
            ->setPerPageVisibilityDisabled()
            ->setPaginationVisibilityDisabled()
            ->setPaginationDisabled()
            ->setSearchDisabled();
    }

    public function columns(): array
    {
        $totalCount = CarQuote::query()
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])->count();

        return [
            Column::make('Lead Source', 'source'),
            Column::make('Count By LeadSource')->label(fn ($row) => $row->leadSourceCount),
            Column::make('Percentage')->label(fn ($row) => number_format((float) (($row->leadSourceCount / $totalCount) * 100), 2, '.', '').'%'),
        ];
    }

    public function builder(): Builder
    {
        return CarQuote::query()
            ->select(
                DB::raw('count(*) as leadSourceCount'),
            )
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->groupBy('source');
    }
}
