<?php

namespace App\Services;

use App\Models\LeadSource;
use App\Models\PaymentStatus;
use App\Enums\PaymentStatusEnum;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\Team;
use App\Models\Tier;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeadListReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {

        dd($request);
        // CarQuote::query()->select('payment_status_id', 'quote_status_id')
        // ->join('user', 'users.id')
        // $query = CarQuote::query()
        //     ->select(
        //         'users.id as advisorId',
        //         DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
        //         DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
        //         'quote_batches.name as batch_name',
        //         'users.name as advisor_name',
        //         'quote_batches.id as quote_batch_id',
        //         DB::raw('SUM(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
        //         DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as new_leads'),
        //         DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as not_interested'),
        //         DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as in_progress'),
        //         DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
        //         DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as bad_leads'),
        //         DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as sale_leads'),
        //         DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as created_sale_leads'),
        //         DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END)  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" as afia_renewals_count'),
        //         DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
        //     )
        //     ->join('users', 'users.id', 'car_quote_request.advisor_id')
        //     ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        //     ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
        //     ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
        //     ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
        //     ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
        //     ->where('users.is_active', true)
        //     ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
        //     ->orderBy('car_quote_request.quote_batch_id')->orderBy('users.email');

        // if (! auth()->user()->hasRole(RolesEnum::LeadPool)) {
        //     $userIds = $this->walkTree(auth()->user()->id);
        //     $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        // }

        // $filters = [
        //     'uuid' => $request->uuid,
        //     'advisorAssignedDates' => $request->advisorAssignedDates,
        //     'tiersFilter' => $request->tiers,
        //     'leadSourceFilter' => $request->leadSources,
        //     'teamsFilter' => $request->teams,
        //     'ecommerceFilter' => $request->is_ecommerce,
        //     'advisorsFilter' => $request->advisors,
        //     'advisorId' => $request->advisorId,
        //     'page' => $request->page,
        // ];

        // $query = $this->applyFilters($query, $filters);

        // $query = $query->get();

        // // map operation to calculate gross and net conversions of records
        // $extendedQuery = $query->map(function ($row) {
        //     $netDenominator = $row->total_leads - $row->bad_leads;
        //     $grossDenominator = $row->total_leads;
        //     $row->net_conversion = (float) $netDenominator > 0 ? round(($row->sale_leads / $netDenominator) * 100, 2) : 0;
        //     $row->gross_conversion = (float) $grossDenominator > 0 ? round(($row->sale_leads / $grossDenominator) * 100, 2) : 0;

        //     return $row;
        // });

        // return $extendedQuery;

    }

    public function getFilterOptions()
    {
        $loginUserId = auth()->user()->id;
        $teamIds = $this->getUserTeams($loginUserId);
        $teams = Team::whereIn('id', $teamIds->pluck('id'))
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $tiers = Tier::query()
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $leadSource = LeadSource::query()
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($source) => $source->name)
            ->toArray();
         $paymentStatus = PaymentStatus::query()
            ->orderBy('text')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($paymentStatus) => $paymentStatus->text)
            ->toArray();
        
        return [
            'tiers' => $tiers,
            'teams' => $teams,
            'leadSource' => $leadSource,
            'paymentStatus' => $paymentStatus,
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

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) :
                ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) : Carbon::parse(now())->endOfDay()->format($dateFormat);

        $query->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);

        if (isset($filters->tiers) && count($filters->tiers) > 0) {
            info('tiersFilter are : '.json_encode($filters->tiers));
            $query->whereIn('car_quote_request.tier_id', $filters->tiers);
        }
        if (isset($filters->teams) && count($filters->teams) > 0) {
            info('teamsFilter are : '.json_encode($filters->teams));
            $value = $filters->teams;
            $query->whereIn('users.id', function ($query) use ($value) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $value);
            });
        }

        if (isset($filters->isCommercial) && $filters->isCommercial != 'All') {
            $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
            $query->where('car_model.is_commercial', '=', $filters->isCommercial);
        }

        return $query;
    }
}
