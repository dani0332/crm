<?php

namespace App\Http\Livewire;

use App\Models\CarQuote;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class AdvisorAssignedCarQuotesTable extends DataTableComponent
{
    public $advisorId;
    public $leadType;
    public function configure(): void
    {
        $this->setPrimaryKey('id')
          ->setColumnSelectDisabled()
          ->setFilterLayoutSlideDown();
    }

    public function columns(): array
    {
        return [
            Column::make('Lead Code', 'uuid'),
            Column::make('Customer Name')->label(fn ($row) => $row->fullName),
            Column::make('Lead Status', 'quote_status_id.text'),
        ];
    }

    public function builder(): Builder
    {
        info('advisorId '.$this->advisorId);
        info('leadType '.$this->leadType);
        $query = CarQuote::query()
        ->select(
            DB::raw("CONCAT('first_name', ' ', 'last_name') as fullName"),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->where('car_quote_request.advisor_id', $this->advisorId)
        ->orderBy('car_quote_request.created_at', 'desc');
        if ($this->leadType == 'new_leads') {
            info('inside lead type new');
            $query->where('car_quote_request.quote_status_id', 40);
        }
        if ($this->leadType == 'not_interested') {
            info('inside lead type not interested');
            $query->where('car_quote_request.quote_status_id', 8);
        }
        if ($this->leadType == 'in_progress') {
            info('inside lead type in progress');
            $query->whereIn('car_quote_request.quote_status_id', [2, 24, 25]);
        }

        return $query;
    }
}
