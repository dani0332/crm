<?php

namespace App\Http\Livewire;

use App\Enums\GenericRequestEnum;
use App\Enums\SkipPlansEnum;
use App\Models\RenewalsUploadLeads;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class UploadedRenewalLeadsTable extends DataTableComponent
{
    protected $model = RenewalsUploadLeads::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('updated_at', 'desc')
            ->setFilterLayoutSlideDown()
            ->setSearchDisabled()
            ->setPerPageVisibilityDisabled()
            ->setColumnSelectDisabled()
            ->setPaginationVisibilityDisabled()
            ->setConfigurableAreas([
                'after-pagination' => 'livewire.pagination',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Upload Type', 'renewal_import_type'),
            Column::make('Upload Code', 'renewal_import_code'),
            Column::make('File name', 'file_name'),
            Column::make('Total records', 'total_records'),
            Column::make('Good', 'good')
                ->format(
                    function ($value, $row, Column $column) {
                        return isset($value) ? '<a href="'.url('renewals/uploaded-leads').'/'.$row->id.'/validation-passed" title="View Passed Validation" class="btn-passed">'.$value.'</a>' : '';
                    }
                )
                ->html(),
            Column::make('Bad', 'cannot_upload')
                ->format(
                    function ($value, $row, Column $column) {
                        return isset($value) ? '<a href="'.url('renewals/uploaded-leads').'/'.$row->id.'/validation-failed" title="View Failed Validation" class="btn-failed">'.$value.'</a>' : '';
                    }
                )
                ->html(),
            Column::make('Status', 'status'),
            Column::make('Skip Plans')->format(function ($skipPlans) {
                return (! $skipPlans) ? GenericRequestEnum::No : (($skipPlans == SkipPlansEnum::NON_GCC) ? 'YES - NON GCC' : GenericRequestEnum::Yes);
            }),
            Column::make('Submitted By', 'createdby.name'),
            Column::make('Submitted At', 'created_at'),
            Column::make('Updated At', 'updated_at'),
        ];
    }

    // simple pagination functions

    public function builder(): Builder
    {
        return RenewalsUploadLeads::query();
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
