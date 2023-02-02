<?php

namespace App\Http\Livewire;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\ReportsLeadTypeEnum;
use App\Models\CarQuote;
use App\Models\QuoteBatches;
use App\Traits\GetUserTreeTrait;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class AdvisorAssignedCarQuotesTable extends DataTableComponent
{
    use GetUserTreeTrait;

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
        $batch = QuoteBatches::where('start_date', $this->startDate)->where('end_date', $this->endDate)->first();
        $query = CarQuote::query()
        ->select(
            DB::raw("CONCAT('first_name', ' ', 'last_name') as fullName"),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->join('user_team', 'users.id', 'user_team.user_id')
        ->join('teams', function ($join) {
            $join->on('user_team.team_id', '=', 'teams.id');
            $join->on('users.sub_team_id', '=', 'teams.id');
        })
        ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        ->whereNull('car_quote_request.renewal_import_code')
        ->where('car_quote_request.advisor_id', $this->advisorId)
        ->orderBy('car_quote_request.created_at', 'desc');
        if ($batch != null) {
            info('batch : '.json_encode($batch->id));
            $query->where('quote_batch_id', $batch->id);
        }
        if ($this->leadType == ReportsLeadTypeEnum::NEW_LEADS) {
            info('inside lead type new');
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::NewLead);
        }
        if ($this->leadType == ReportsLeadTypeEnum::NOT_INTERESTED) {
            info('inside lead type not interested');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::PriceTooHigh, QuoteStatusEnum::PolicyPurchasedBeforeFirstCall, QuoteStatusEnum::NotInterested, QuoteStatusEnum::NotEligibleForInsurance, QuoteStatusEnum::NotLookingForMotorInsurance, QuoteStatusEnum::NonGccSpec]);
        }
        if ($this->leadType == ReportsLeadTypeEnum::IN_PROGRESS) {
            info('inside lead type in progress');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::NotContactablePe, QuoteStatusEnum::FollowedUp, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::Quoted]);
        }
        if ($this->leadType == ReportsLeadTypeEnum::MANUAL_CREATED) {
            info('inside lead type MANUAL_CREATED');
            $query->where('source', LeadSourceEnum::IMCRM);
        }
        if ($this->leadType == ReportsLeadTypeEnum::BAD_LEAD) {
            info('inside lead type BAD_LEAD');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        }
        if ($this->leadType == ReportsLeadTypeEnum::AFIA_RENEWALS_COUNT) {
            info('inside lead type AFIA_RENEWALS_COUNT');
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::AfiaRenewal);
        }
        if ($this->leadType == ReportsLeadTypeEnum::SALE_LEAD) {
            info('inside lead type SALE_LEAD');
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::TransactionApproved);
        }
        if ($this->leadType == ReportsLeadTypeEnum::CREATED_SALE_LEAD) {
            info('inside lead type CREATED_SALE_LEAD');
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::TransactionApproved)->where('source', LeadSourceEnum::IMCRM);
        }

        if ($this->leadType == ReportsLeadTypeEnum::OTHERS) {
            info('inside lead type in OTHERS');

            $query->whereNotIn('car_quote_request.quote_status_id', [
                QuoteStatusEnum::NewLead, QuoteStatusEnum::PriceTooHigh, QuoteStatusEnum::PolicyPurchasedBeforeFirstCall, QuoteStatusEnum::NotInterested,
                QuoteStatusEnum::NotEligibleForInsurance, QuoteStatusEnum::NotLookingForMotorInsurance, QuoteStatusEnum::NonGccSpec, QuoteStatusEnum::NotContactablePe,
                QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::Quoted, QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::Fake, QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::AfiaRenewal]);
        }

        return $query;
    }
}
