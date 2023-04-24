<?php

namespace App\Http\Livewire;

use App\Models\RenewalStatusProcess;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RenewalBatchesPlanProcessTable extends DataTableComponent
{
    public $batch;

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
            Column::make('Id'),
            Column::make('Batch'),
            Column::make('Total Leads'),
            Column::make('Completed', 'total_completed'),
            Column::make('Failed', 'total_failed'),
            Column::make('Status'),
            Column::make('User', 'createdBy.email'),
            Column::make('Created At'),
            Column::make('Updated At'),
        ];
    }

    public function builder(): Builder
    {
        return RenewalStatusProcess::query()
            ->where([
                'batch' => $this->batch,
            ]);
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
