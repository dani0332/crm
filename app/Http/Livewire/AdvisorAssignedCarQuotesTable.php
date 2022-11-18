<?php

namespace App\Http\Livewire;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CarQuote;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class AdvisorAssignedCarQuotesTable extends DataTableComponent
{
    public $advisorId;
    public $leadType;
    public $startDate;
    public $endDate;
    protected string $emptyMessage = 'No data available';

    public function configure(): void
    {
        $this->setPrimaryKey('id')
          ->setColumnSelectDisabled()
          ->setSearchDisabled()
          ->setPerPageVisibilityDisabled();
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
        info('advisorId : '.$this->advisorId);
        info('leadType : '.$this->leadType);
        info('start date : '.$this->startDate);
        info('end date : '.$this->endDate);
        $query = CarQuote::query()
        ->select(
            DB::raw("CONCAT('first_name', ' ', 'last_name') as fullName"),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->whereBetween('car_quote_request.created_at', [$this->startDate, $this->endDate])
        ->where('car_quote_request.advisor_id', $this->advisorId)
        ->orderBy('car_quote_request.created_at', 'desc');
        if ($this->leadType == 'new_leads') {
            info('inside lead type new');
            $query->whereNotNull('car_quote_request.advisor_id')->where('car_quote_request.quote_status_id', QuoteStatusEnum::NewLead);
        }
        if ($this->leadType == 'not_interested') {
            info('inside lead type not interested');
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::NewLead);
        }
        if ($this->leadType == 'in_progress') {
            info('inside lead type in progress');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Quoted, QuoteStatusEnum::FollowedUp, QuoteStatusEnum::InNegotiation]);
        }
        if ($this->leadType == 'manual_created') {
            info('inside lead type in progress');
            $query->where('source', LeadSourceEnum::IMCRM);
        }
        if ($this->leadType == 'bad_leads') {
            info('inside lead type in progress');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        }
        if ($this->leadType == 'sale_leads') {
            info('inside lead type in progress');
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::PolicyIssued);
        }
        if ($this->leadType == 'created_sale_leads') {
            info('inside lead type in progress');
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::TransactionApproved)->where('source', LeadSourceEnum::IMCRM);
        }

        return $query;
    }
}
