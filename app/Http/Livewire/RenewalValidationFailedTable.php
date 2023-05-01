<?php

namespace App\Http\Livewire;

use App\Enums\RenewalProcessStatuses;
use App\Models\RenewalQuoteProcess;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RenewalValidationFailedTable extends DataTableComponent
{
    public $batch_id;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
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
            Column::make('File Name', 'renewalUploadLead.file_name'),
            Column::make('Quote Type'),
            Column::make('Policy Number'),
            Column::make('Status'),
            Column::make('Validation Errors')
                ->format(
                    function ($value, $row, Column $column) {
                        $html = '';
                        foreach ($row->validation_errors as $key => $error) {
                            $html .= '<li>'.$error.'</li>';
                        }

                        return '<ul class="list-disc marker:text-red-600">'.$html.'</ul>';
                    }
                )
                ->html(),
            Column::make('Created at', 'created_at'),
        ];
    }

    public function builder(): Builder
    {
        return RenewalQuoteProcess::query()
            ->where('renewals_upload_lead_id', $this->batch_id)
            ->whereIn('renewal_quote_processes.status', [RenewalProcessStatuses::BAD_DATA, RenewalProcessStatuses::VALIDATION_FAILED]);
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
