<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\ReportsLeadTypeEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\QuoteBatches;
use App\Models\Team;
use App\Models\Tier;
use App\Models\User;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdvisorConversionReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $query = CarQuote::query()
            ->select(
                'users.id as advisorId',
                'quote_batches.start_date as start_date',
                'quote_batches.end_date as end_date',
                'quote_batches.name as batch_name',
                'users.name as advisor_name',
                'quote_batches.id as quote_batch_id',
                DB::raw('count(car_quote_request.id) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = ' . QuoteStatusEnum::NewLead . ' THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (' . QuoteStatusEnum::PriceTooHigh . ', ' . QuoteStatusEnum::PolicyPurchasedBeforeFirstCall . ', ' . QuoteStatusEnum::NotInterested . ', ' . QuoteStatusEnum::NotEligibleForInsurance . ', ' . QuoteStatusEnum::NotLookingForMotorInsurance . ', ' . QuoteStatusEnum::NonGccSpec . ',' . QuoteStatusEnum::AMLScreeningFailed . ') THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (' . QuoteStatusEnum::NotContactablePe . ', ' . QuoteStatusEnum::FollowupCall . ', ' . QuoteStatusEnum::Interested . ', ' . QuoteStatusEnum::NoAnswer . ', ' . QuoteStatusEnum::Quoted . ', ' . QuoteStatusEnum::PaymentPending . ',' . QuoteStatusEnum::AMLScreeningCleared . ',' . QuoteStatusEnum::PendingQuote . ') THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "' . LeadSourceEnum::IMCRM . '" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (' . QuoteStatusEnum::Duplicate . ',' . QuoteStatusEnum::Fake . ') THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (' . QuoteStatusEnum::TransactionApproved . ',' . QuoteStatusEnum::PolicyIssued . ') THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "' . LeadSourceEnum::IMCRM . '" and car_quote_request.quote_status_id in (' . QuoteStatusEnum::TransactionApproved . ',' . QuoteStatusEnum::PolicyIssued . ') THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = ' . QuoteStatusEnum::IMRenewal . ' THEN 1 ELSE 0 END) as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (' . QuoteStatusEnum::Duplicate . ',' . QuoteStatusEnum::Fake . ') and car_quote_request.source = "' . LeadSourceEnum::IMCRM . '" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
            ->orderBy('car_quote_request.quote_batch_id')->orderBy('users.email');

        if (!auth()->user()->hasRole(RolesEnum::Admin)) {
            $userIds = $this->walkTree(auth()->user()->id);
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        }

        $query = $this->applyFilters($query, $request->all());
        info('query : ' . $query->toSql());
        info('data : ' . json_encode($query->getBindings()));
        return $query->paginate(10)
            ->withQueryString();
    }

    public function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $loginUserId = auth()->user()->id;
        $advisors = User::whereIn('id', [$loginUserId])
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        // TODO: add teams logic
        $teamIds = $this->getUserTeams($loginUserId);
        $teams = Team::whereIn('id', $teamIds->pluck('id'))
                ->select('name', 'id')
                ->orderBy('name')
                ->where('is_active', 1)
                ->get()
                ->keyBy('id')
                ->map(fn ($users) => $users->name)
                ->toArray();

        $batches = QuoteBatches::query()
            ->select('name', 'start_date', 'end_date', 'id')
            ->orderBy('id')
            ->get()
            ->keyBy('id')
            ->map(fn ($batch) => $batch->name . '-(' . $batch->start_date . ' to ' . $batch->end_date . ')')
            ->toArray();
        $tiers = Tier::query()
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $leadSources = LeadSource::query()
            ->select('name')
            ->where('is_active', 1)->where('is_applicable_for_rules', 0)
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();
        return [
            'maxDays' => $maxDays,
            'batches' => $batches,
            'tiers' => $tiers,
            'leadSources' => $leadSources,
            'advisors' => $advisors,
            'teams' => $teams,
        ];
    }

    public function getDefaultFilters()
    {
        $advisorAssignedDates = [
            Carbon::parse(now())->startOfDay()->format('Y-m-d'),
            Carbon::parse(now())->endOfDay()->format('Y-m-d'),
        ];
        return [
            'advisorAssignedDates' => $advisorAssignedDates,
        ];
    }


    public function getAdvisorsAssignedLeads($filters)
    {
        $query = CarQuote::query()
            ->select(
                DB::raw("CONCAT(car_quote_request.first_name, ' ', car_quote_request.last_name) as fullName"),
                'car_quote_request.code as cdbId',
                'quote_status.text as quoteStatusName'
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->join('quote_status', 'quote_status.id', 'car_quote_request.quote_status_id')
            ->whereNull('car_quote_request.renewal_import_code')
            ->orderBy('car_quote_request_detail.advisor_assigned_date', 'desc');
        info('ajax filters : ' . json_encode($filters));
        $query = $this->applyFilters($query, $filters);
        info('ajax query : ' . $query->toSql());
        info('ajax data : ' . json_encode($query->getBindings()));
        return $query->paginate(10);
    }



    public function applyFilters($query, $filters)
    {
        if (isset($filters->advisorId)) {
            $query = $query->where('car_quote_request.advisor_id', $filters->advisorId);
        }

        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $batch = null;
        if (isset($filters['quoteBatchId'])) {
            $batch = QuoteBatches::where('id', $filters['quoteBatchId'])->first();
        }

        if ($batch != null) {
            info('batch : ' . json_encode($batch->id));
            $query->where('quote_batch_id', $batch->id);
        }
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        info('date : ' . json_encode($filters));
        $startDate = isset($filters['advisorAssignedDates']) ?
            Carbon::parse($filters['advisorAssignedDates'][0])->startOfDay()->format($dateFormat) :
            Carbon::parse(now())->startOfDay()->format($dateFormat);

        $endDate = isset($filters['advisorAssignedDates']) ?
            Carbon::parse($filters['advisorAssignedDates'][1])->endOfDay()->format($dateFormat) :
            Carbon::parse(now())->endOfDay()->format($dateFormat);

        $query->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);
        if (isset($filters->ecommerceFilter)) {
            info('ecommerceFilter are : ' . json_encode($filters->ecommerceFilter));
            $query->where('car_quote_request.is_ecommerce', $filters->ecommerceFilter == 'Yes' ? 1 : 0);
        }
        if (isset($filters->excludeCreatedLeadsFilter)) {
            info('excludeCreatedLeadsFilter are : ' . json_encode($filters->excludeCreatedLeadsFilter));
            if ($filters->excludeCreatedLeadsFilter == 'yes') {
                info('inside excludeCreatedLeadsFilter');
                $query->where('car_quote_request.source', '!=', 'IMCRM');
            }
        }
        if (isset($filters->tiersFilter) && count($filters->tiersFilter) > 0) {
            info('tiersFilter are : ' . json_encode($filters->tiersFilter));
            $query->whereIn('car_quote_request.tier_id', $filters->tiersFilter);
        }
        if (isset($filters->leadSourceFilter) && count($filters->leadSourceFilter) > 0) {
            info('leadSourceFilter are : ' . json_encode($filters->leadSourceFilter));
            $query->whereIn('car_quote_request.source', $filters->leadSourceFilter);
        }
        if (isset($filters->teamsFilter) && count($filters->teamsFilter) > 0) {
            info('teamsFilter are : ' . json_encode($filters->teamsFilter));
            $value = $filters->teamsFilter;
            $query->whereIn('users.id', function ($query) use ($value) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $value);
            });
        }
        if (isset($filters->advisorsFilter) && count($filters->advisorsFilter) > 0) {
            info('advisorsFilter are : ' . json_encode($filters->advisorsFilter));
            $query->whereIn('car_quote_request.advisor_id', $filters->advisorsFilter);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::NEW_LEADS) {
            info('inside lead type new');
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::NewLead);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::NOT_INTERESTED) {
            info('inside lead type not interested');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::PriceTooHigh, QuoteStatusEnum::PolicyPurchasedBeforeFirstCall, QuoteStatusEnum::NotInterested, QuoteStatusEnum::NotEligibleForInsurance, QuoteStatusEnum::NotLookingForMotorInsurance, QuoteStatusEnum::NonGccSpec, QuoteStatusEnum::AMLScreeningFailed]);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::IN_PROGRESS) {
            info('inside lead type in progress');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::NotContactablePe, QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::Quoted, QuoteStatusEnum::PaymentPending, QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::PendingQuote]);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::MANUAL_CREATED) {
            info('inside lead type MANUAL_CREATED');
            $query->where('source', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::BAD_LEAD) {
            info('inside lead type BAD_LEAD');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::AFIA_RENEWALS_COUNT) {
            info('inside lead type AFIA_RENEWALS_COUNT');
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::IMRenewal);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::SALE_LEAD) {
            info('inside lead type SALE_LEAD');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued]);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::CREATED_SALE_LEAD) {
            info('inside lead type CREATED_SALE_LEAD');
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued])->where('source', LeadSourceEnum::IMCRM);
        }

        return $query;
    }
}
