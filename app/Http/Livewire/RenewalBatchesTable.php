<?php

namespace App\Http\Livewire;

use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalQuoteProcess;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RenewalBatchesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('created_at', 'desc')
            ->setSearchDisabled()
            ->setPerPageVisibilityDisabled()
            ->setColumnSelectDisabled()
            ->setPaginationVisibilityDisabled()
            ->setEmptyMessage('No data found')
            ->setConfigurableAreas([
                'after-pagination' => 'livewire.pagination',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Batch'),
            Column::make('Actions')
                ->label(function ($row) {
                    return '<div class="flex gap-2"><a href="'.route('batch-plans-processes', $row->batch).'" title="Fetch Plans" class="btn">Fetch Plans</a>'.
                        '<a href="'.route('batch-renewal-detail', $row->batch).'" title="Send Emails" class="btn-2">Send Emails</a></div>';
                })->html(),
        ];
    }

    public function builder(): Builder
    {
        return RenewalQuoteProcess::query()
            ->select('batch as renewal_batch')
            ->where([
                'quote_type' => QuoteTypeShortCode::CAR,
                'type' => RenewalsUploadType::UPDATE_LEADS,
            ])
            ->whereNotNull('batch')
            ->where('batch', '<>', '')
            ->groupBy('batch');
    }

    public function getCurrentPage()
    {
        return $this->page;
    }

    protected function executeQuery()
    {
        return $this->getBuilder()->simplePaginate($this->getPerPage(), ['*'], $this->getComputedPageName());
    }
}
