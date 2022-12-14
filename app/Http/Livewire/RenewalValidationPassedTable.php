<?php

namespace App\Http\Livewire;

use App\Enums\RenewalProcessStatuses;
use App\Models\RenewalQuoteProcess;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RenewalValidationPassedTable extends DataTableComponent
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
            Column::make('Id')->hideIf(true),
            Column::make('Batch'),
            Column::make('File name', 'renewalUploadLead.file_name'),
            Column::make('Quote type'),
            Column::make('Policy Number'),
            Column::make('Status'),
            Column::make('Quote')
              ->label(
                  fn ($row) => '<a target="_blank" href="./quote-redirect/'.$row->id.'" class="btn btn-primary btn-sm fetch-plans">View Quote</a>'
              )
              ->html(),
            Column::make('Created at'),
        ];
    }

    public function builder(): Builder
    {
        return RenewalQuoteProcess::query()
          ->where('renewals_upload_lead_id', $this->batch_id)
          ->whereIn('renewal_quote_processes.status', [RenewalProcessStatuses::VALIDATED, RenewalProcessStatuses::PROCESSED, RenewalProcessStatuses::PLANS_FETCHED, RenewalProcessStatuses::EMAIL_SENT]);
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
