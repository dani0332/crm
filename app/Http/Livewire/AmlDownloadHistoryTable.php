<?php

namespace App\Http\Livewire;

use App\Models\SanctionListDownloads;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class AmlDownloadHistoryTable extends DataTableComponent
{
    public $url;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('updated_at', 'desc')
            ->setColumnSelectDisabled()
            ->setSearchDisabled()
            ->setPerPageVisibilityDisabled()
            ->setFilterLayoutSlideDown();

        $this->setPaginationMethod('simple');
    }

    public function columns(): array
    {
        return [
            Column::make('Id'),
            Column::make('File Name')
                ->searchable()
                ->format(
                    function ($value, $row, Column $column) {
                        if ($row->file_name === null) {
                            return 'NULL';
                        } else {
                            return "<a href='".$this->url.'/'.$row->file_name."' title='Download File' target='_blank' class='text-sky-700'>".$row->file_name.'</a>';
                        }
                    }
                )
                ->html(),
            Column::make('Source')
                ->sortable(),
            Column::make('Total Records'),
            BooleanColumn::make('Is Processed'),
            Column::make('Created at'),
            Column::make('Updated at')
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('File Name')
                ->config([
                    'placeholder' => 'Search by File Name',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('sanction_list_downloads.file_name', $value);
                }),
            SelectFilter::make('Is Processed')
                ->options([
                    '' => 'All',
                    '1' => 'Yes',
                    '0' => 'No',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '1') {
                        $builder->where('is_processed', true);
                    } elseif ($value === '0') {
                        $builder->where('is_processed', false);
                    }
                }),
        ];
    }

    public function builder(): Builder
    {
        return SanctionListDownloads::query();
    }

    // custom pagination

    // public function getCurrentPage()
    // {
    //     return $this->page;
    // }

    // protected function executeQuery()
    // {
    //     return $this->getBuilder()->simplePaginate($this->getPerPage(), ['*'], $this->getComputedPageName());
    // }
}
