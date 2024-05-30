<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\Tier;
use App\Services\ApplicationStorageService;
use App\Services\BaseService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeadDistributionReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $query = CarQuote::leftJoin('tiers', 'tiers.id', '=', 'car_quote_request.tier_id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->filterBySegment()
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->select(DB::raw('(

        (SUM(CASE WHEN car_quote_request.auto_assigned = 1 AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END)
        + SUM(CASE WHEN car_quote_request.auto_assigned = 0 AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END)
        + SUM(CASE WHEN car_quote_request.advisor_id IS NULL THEN 1 ELSE 0 END))) AS received_leads,

        SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) AS lead_created, COUNT(*) AS total_leads,

        SUM(CASE WHEN car_quote_request.auto_assigned = 1 AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS auto_assigned,

        SUM(CASE WHEN car_quote_request.auto_assigned = 0 AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS manually_assigned,

        SUM(CASE WHEN car_quote_request.advisor_id IS NULL THEN 1 ELSE 0 END) AS unassigned_leads'), 'tiers.name AS tier_name')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('tiers.name')
            ->orderBy('tiers.name');

        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            $query->where('car_quote_request.advisor_id', auth()->user()->id);
        } else {
            if (! auth()->user()->hasRole(RolesEnum::LeadPool)) {
                $userIds = $this->walkTree(auth()->user()->id);
                $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
            }
        }

        $query = $this->applyFilters($query, $request->all());

        return $query->paginate(15)
            ->withQueryString();
    }

    public function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $tiers = Tier::query()
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        return [
            'maxDays' => $maxDays,
            'tiers' => $tiers,
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
            'createdAtDates' => $advisorAssignedDates,
        ];
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;

        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->createdAtDates) ?
            Carbon::parse($filters->createdAtDates[0])->startOfDay()->format($dateFormat) :
                ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

        $endDate = isset($filters->createdAtDates) ?
            Carbon::parse($filters->createdAtDates[1])->endOfDay()->format($dateFormat) :
            Carbon::parse(now())->endOfDay()->format($dateFormat);

        $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);

        if (isset($filters->tiers) && count($filters->tiers) > 0) {
            $query->whereIn('car_quote_request.tier_id', $filters->tiers);
        }

        if (isset($filters->assignmentTypes) && $filters->assignmentTypes != 'All') {
            $query->where('car_quote_request.assignment_type', $filters->assignmentTypes);
        }

        if (isset($filters->isCommercial) && $filters->isCommercial != 'All') {
            $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
            $query->where('car_model.is_commercial', '=', $filters->isCommercial);
        }

        return $query;
    }
}
