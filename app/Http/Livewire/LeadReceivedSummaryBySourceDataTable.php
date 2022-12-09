<?php

namespace App\Http\Livewire;

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
        ->setSearchDisabled();
    }

    public function columns(): array
    {
        /*

        SELECT count(*) AS leadSourceCount,
            source,
            count(*) / (
                SELECT count(*)
                FROM car_Quote_Request
                WHERE created_at = '2021-01-26 05:41:32'
                ) AS PERCENT
        FROM car_quote_request
        WHERE created_at = '2021-01-26 05:41:32'
        GROUP BY source

        */
        return [
            Column::make('Lead Source', 'source'),
            Column::make('Count By LeadSource')->label(fn ($row) => $row->leadSourceCount),
            Column::make('Percentage')->label(fn ($row) => number_format((float) $row->percent, 2, '.', '')),
        ];
    }

    public function builder(): Builder
    {
        return CarQuote::query()
            ->select(
                DB::raw('count(*) as leadSourceCount'),
                DB::raw("count(*) / (SELECT count(*)	FROM car_Quote_Request WHERE created_at = '2021-01-26 05:41:32' ) AS percent"),
            )
            ->groupBy('source');
    }
}
