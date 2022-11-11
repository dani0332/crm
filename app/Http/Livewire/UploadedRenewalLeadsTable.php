<?php

namespace App\Http\Livewire;

use App\Enums\RolesEnum;
use App\Models\RenewalsUploadLeads;
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
            ->setColumnSelectDisabled();
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Upload Type', 'renewal_import_type'),
            Column::make('Upload Code', 'renewal_import_code'),
            Column::make('File name', 'file_name'),
            Column::make('Total records', 'total_records')
                ->sortable(),
            Column::make('Good', 'good')
                ->format(
                    function ($value, $row, Column $column) {
                        return '<a href="'.url('renewals/uploaded-leads').'/'.$row->id.'/validation-passed" title="View Passed Validation" class="text-sky-700">'.$row->good.'</a>';
                    }
                )
                ->html(),
            Column::make('Bad', 'cannot_upload')
                ->format(
                    function ($value, $row, Column $column) {
                        return '<a href="'.url('renewals/uploaded-leads').'/'.$row->id.'/validation-failed" title="View Failed Validation" class="text-sky-700">'.$row->cannot_upload.'</a>';
                    }
                )
                ->html(),
            Column::make('Status', 'status'),
            Column::make('Created By', 'createdby.name'),
            Column::make('Created at', 'created_at'),
            Column::make('Updated at', 'updated_at'),

            Column::make('Action')
                ->label(
                    function ($row, Column $column) {
                        if ($row->status == 'Completed' && $row->renewal_import_type == 'create') {
                            return '<a href="'.url('renewals').'/'.$row->id.'/fetch-plans" title="Fetch Plans" class="btn">Fetch Plans</a>';
                        }
                    }
                )
                ->html()
                ->hideIf(! auth()->user()->hasAnyRole([RolesEnum::RenewalsManager, RolesEnum::Admin])),
        ];
    }
}
