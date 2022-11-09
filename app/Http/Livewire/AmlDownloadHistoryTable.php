<?php

namespace App\Http\Livewire;

use App\Models\SanctionListDownloads;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AmlDownloadHistoryTable extends DataTableComponent
{
    public $url;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('updated_at', 'desc')
            ->setColumnSelectDisabled()
            ->setFilterLayoutSlideDown();

        // disabled pagination count
        $this->setPaginationDisabled();
        $this->setPaginationVisibilityDisabled();
        $this->setConfigurableAreas([
            'after-pagination' => 'partials.pagination',
          ]);
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

    // custom pagination
    public function builder(): Builder
    {
        return SanctionListDownloads::query()->offset($this->getOffset())->limit(10);
    }

    public function getOffset()
    {
        return ($this->page - 1) * 10;
    }

    public function getCurrentPage()
    {
        return $this->page;
    }

    public function gotoNext()
    {
        $this->page++;
        $this->paginators['page'] = $this->page;
        usleep(500000);
        $this->emit('refreshDatatable');
    }

    public function gotoPrev()
    {
        if ($this->page > 1) {
            $this->page--;
            $this->paginators['page'] = $this->page;
            usleep(500000);
            $this->emit('refreshDatatable');
        }
    }
}
