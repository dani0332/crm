<?php

namespace App\Http\Livewire;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\ReportsLeadTypeEnum;
use App\Models\CarQuote;
use App\Models\QuoteBatches;
use App\Traits\GetUserTreeTrait;
use Carbon\Carbon;
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
    public $createdAtFilter;
    public $ecommerceFilter;
    public $excludeCreatedLeadsFilter;
    public $batchNumberFilter;
    public $tiersFilter;
    public $leadSourceFilter;
    public $teamsFilter;
    public $advisorsFilter;
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
        $batch = QuoteBatches::where('start_date', $this->startDate)->where('end_date', $this->endDate)->first();

        $query = CarQuote::query()
        ->select(
            DB::raw("CONCAT('first_name', ' ', 'last_name') as fullName"),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        ->join('user_team', 'user_team.user_id', 'users.id')
        ->join('teams', 'teams.id', 'user_team.team_id')
        ->whereNull('car_quote_request.renewal_import_code')
        ->where('car_quote_request.advisor_id', $this->advisorId)
        ->orderBy('car_quote_request.created_at', 'desc');

        if ($batch != null) {
            info('batch : '.json_encode($batch->id));
            $query->where('quote_batch_id', $batch->id);
        }
        if ($this->createdAtFilter != '') {
            info('createdAtFilter are : '.json_encode($this->createdAtFilter));
            $startDate = Carbon::parse(explode('|', $this->createdAtFilter)[0]);
            $endDate = Carbon::parse(explode('|', $this->createdAtFilter)[1]);
            $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
        }
        if ($this->ecommerceFilter != '') {
            info('ecommerceFilter are : '.json_encode($this->ecommerceFilter));
            $query->where('car_quote_request.is_ecommerce', $this->ecommerceFilter == 'yes' ? 1 : 0);
        }
        if ($this->excludeCreatedLeadsFilter != '') {
            info('excludeCreatedLeadsFilter are : '.json_encode($this->excludeCreatedLeadsFilter));
            if ($this->excludeCreatedLeadsFilter == 'yes') {
                info('inside excludeCreatedLeadsFilter');
                $query->where('car_quote_request.source', '!=', 'IMCRM');
            }
        }
        if ($this->tiersFilter != '' && count($this->tiersFilter) > 0) {
            info('tiersFilter are : '.json_encode($this->tiersFilter));
            $query->whereIn('car_quote_request.tier_is', $this->tiersFilter);
        }
        if ($this->leadSourceFilter != '' && count($this->leadSourceFilter) > 0) {
            info('leadSourceFilter are : '.json_encode($this->leadSourceFilter));
            $query->whereIn('car_quote_request.source', $this->leadSourceFilter);
        }
        if ($this->teamsFilter != '' && count($this->teamsFilter) > 0) {
            info('teamsFilter are : '.json_encode($this->teamsFilter));
            $query->whereIn('teams.id', $this->teamsFilter);
        }
        if ($this->advisorsFilter != '' && count($this->advisorsFilter) > 0) {
            info('advisorsFilter are : '.json_encode($this->advisorsFilter));
            $query->whereIn('car_quote_request.advisor_id', $this->advisorsFilter);
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
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::NotContactablePe, QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::Quoted]);
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
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued]);
        }
        if ($this->leadType == ReportsLeadTypeEnum::CREATED_SALE_LEAD) {
            info('inside lead type CREATED_SALE_LEAD');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued])->where('source', LeadSourceEnum::IMCRM);
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
