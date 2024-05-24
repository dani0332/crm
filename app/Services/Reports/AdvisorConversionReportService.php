<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\ReportsLeadTypeEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\QuoteBatches;
use App\Models\Team;
use App\Models\Tier;
use App\Services\ApplicationStorageService;
use App\Services\BaseService;
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
                DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
                DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
                'quote_batches.name as batch_name',
                'users.name as advisor_name',
                'quote_batches.id as quote_batch_id',
                DB::raw('SUM(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::PolicyCancelled.' and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as cancelled_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END)  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->filterBySegment()
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->where('users.is_active', true)
            ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
            ->orderBy('car_quote_request.quote_batch_id')->orderBy('users.email');

        if (! auth()->user()->hasRole(RolesEnum::LeadPool)) {
            $userIds = $this->walkTree(auth()->user()->id);
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        }

        $filters = [
            'advisorId' => $request->advisorId,
            'leadType' => $request->leadType,
            'advisorAssignedDates' => $request->advisorAssignedDates,
            'createdAtFilter' => $request->createdAtFilter,
            'ecommerceFilter' => $request->is_ecommerce,
            'excludeCreatedLeadsFilter' => $request->excludeCreatedLeadsFilter,
            'batchNumberFilter' => $request->batches,
            'tiersFilter' => $request->tiers,
            'leadSourceFilter' => $request->leadSources,
            'teamsFilter' => $request->teams,
            'advisorsFilter' => $request->advisors,
            'quoteBatchId' => $request->quote_batch_id,
            'isCommercial' => $request->isCommercial,
            'page' => $request->page,
        ];

        $query = $this->applyFilters($query, $filters);

        $query = $query->get();

        // map operation to calculate gross and net conversions of records
        $extendedQuery = $query->map(function ($row) {
            $netDenominator = $row->total_leads - $row->bad_leads;
            $grossDenominator = $row->total_leads;
            $row->net_conversion = (float) $netDenominator > 0 ? round(($row->sale_leads / $netDenominator) * 100, 2) : 0;
            $row->gross_conversion = (float) $grossDenominator > 0 ? round(($row->sale_leads / $grossDenominator) * 100, 2) : 0;

            return $row;
        });

        return $extendedQuery;

    }

    public function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

        $loginUserId = auth()->user()->id;

        $advisors = [];

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
            ->map(function ($batch) {
                $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
                $start_date = Carbon::parse($batch->start_date)->format($dateFormat);
                $end_date = Carbon::parse($batch->end_date)->format($dateFormat);

                return $batch->name.'-('.$start_date.' to '.$end_date.')';
            })
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
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $advisorAssignedDates = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
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
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->whereNull('car_quote_request.renewal_import_code')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->orderBy('car_quote_request_detail.advisor_assigned_date', 'desc');

        $query = $this->applyFilters($query, $filters);

        return $query->paginate(10);
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;

        if (isset($filters->advisorId)) {
            $query = $query->where('car_quote_request.advisor_id', $filters->advisorId);
        }

        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $batch = null;
        if (isset($filters->quoteBatchId)) {
            $batch = QuoteBatches::where('id', $filters->quoteBatchId)->first();
        }

        if (isset($filters->batchNumberFilter) && count($filters->batchNumberFilter) > 0) {
            $query = $query->whereIn('car_quote_request.quote_batch_id', $filters->batchNumberFilter);
        }

        if ($batch) {
            $query = $query->where('car_quote_request.quote_batch_id', $batch->id);
        }
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) :
                ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) :
                    now()->subDays((int) $maxDays)->startOfDay()->format($dateFormat));

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) :
            Carbon::parse(now())->endOfDay()->format($dateFormat);

        if ($freshLoad) {
            $query->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);
        } elseif (isset($filters->advisorAssignedDates)) {
            $query->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);
        }

        if (isset($filters->ecommerceFilter) && $filters->ecommerceFilter != 'All') {
            $query->where('car_quote_request.is_ecommerce', $filters->ecommerceFilter == 'Yes' ? 1 : 0);
        }
        if (isset($filters->excludeCreatedLeadsFilter)) {
            if ($filters->excludeCreatedLeadsFilter == 'yes') {
                info('inside excludeCreatedLeadsFilter');
                $query->where('car_quote_request.source', '!=', LeadSourceEnum::IMCRM);
            }
        }
        if (isset($filters->tiersFilter) && count($filters->tiersFilter) > 0) {
            $query->whereIn('car_quote_request.tier_id', $filters->tiersFilter);
        }
        if (isset($filters->leadSourceFilter) && count($filters->leadSourceFilter) > 0) {
            $query->whereIn('car_quote_request.source', $filters->leadSourceFilter);
        }
        if (isset($filters->teamsFilter) && count($filters->teamsFilter) > 0) {
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
            $query->whereIn('car_quote_request.advisor_id', $filters->advisorsFilter);
        }

        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::TOTAL_LEADS) {
            $query->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::NEW_LEADS) {
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::NewLead)->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::NOT_INTERESTED) {
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::PriceTooHigh, QuoteStatusEnum::PolicyPurchasedBeforeFirstCall, QuoteStatusEnum::NotInterested, QuoteStatusEnum::NotEligibleForInsurance, QuoteStatusEnum::NotLookingForMotorInsurance, QuoteStatusEnum::NonGccSpec, QuoteStatusEnum::AMLScreeningFailed])->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::IN_PROGRESS) {
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::NotContactablePe, QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::Quoted, QuoteStatusEnum::PaymentPending, QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::PendingQuote])->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::CANCELLED_LEADS) {
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::PolicyCancelled])->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::MANUAL_CREATED) {
            $query->where('source', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::BAD_LEAD) {
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::AFIA_RENEWALS_COUNT) {
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::IMRenewal)->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::SALE_LEAD) {
            $query->where(function ($query) {
                $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued])
                    ->orWhere('car_quote_request.payment_status_id', PaymentStatusEnum::CAPTURED);
            })->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::CREATED_SALE_LEAD) {
            $query->where(function ($query) {
                $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued])
                    ->orWhere('car_quote_request.payment_status_id', PaymentStatusEnum::CAPTURED);
            })->where('source', '=', LeadSourceEnum::IMCRM);
        }

        if (isset($filters->isCommercial) && $filters->isCommercial != 'All') {
            $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
            $query->where('car_model.is_commercial', '=', $filters->isCommercial);
        }

        return $query;
    }
}
