<?php

namespace App\Services\Reports;

use App\Enums\AssignmentTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\UserManager;
use App\Repositories\QuoteTypeRepository;
use App\Services\ApplicationStorageService;
use App\Services\BaseService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeadDistributionReportService extends BaseService
{
    use GetUserTreeTrait;
    use Reportable;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $lob = $request->lob ?? '';

        if (! $lob) {
            return [];
        }

        $filters = $this->getFilters($request);
        $reportDataQuery = $this->buildQuery($lob, $filters);

        return $reportDataQuery->paginate(15)->withQueryString();
    }

    private function getFilters($request)
    {
        return [
            'lob' => $request->lob,
            'createdAtDates' => $request->createdAtDates,
            'assignmentTypes' => $request->assignmentTypes,
            'segment_filter' => $request->segment_filter,
            'sic_advisor_requested' => $request->sic_advisor_requested,
            'page' => $request->page,
        ];
    }

    private function buildQuery($lob, $filters)
    {
        // Build query for Car LOB
        if ($lob === quoteTypeCode::Car) {
            $carQuery = $this->getCarQuoteQuery($lob);

            return $this->applyFiltersForCar($carQuery, $filters);
        }

        // Build query for Personal Quotes
        $personalQuoteQuery = $this->getPersonalQuoteQuery($lob);

        return $this->applyFilters($personalQuoteQuery, $filters);
    }

    private function getCarQuoteQuery($lob)
    {
        $parentTeam = $this->getProductByName($lob);
        // Base query for car quotes with necessary joins
        $carQuoteQuery = CarQuote::query()
            ->select('teams.name AS team_name')
            ->leftJoin('user_team', 'user_team.user_id', '=', 'car_quote_request.advisor_id')
            ->leftJoin('teams', 'teams.id', '=', 'user_team.team_id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->where('teams.parent_team_id', $parentTeam->id)
            ->groupBy('teams.name')
            ->orderBy('teams.name');

        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            $carQuoteQuery->where('car_quote_request.advisor_id', auth()->user()->id);
        } elseif (! $this->hasAdminPrivileges()) {
            $userIds = $this->getEligibleUserIds($lob);
            $carQuoteQuery->whereIn('car_quote_request.advisor_id', $userIds);
        }

        $this->addSelect($carQuoteQuery, 'car_quote_request');

        return $carQuoteQuery;
    }

    // Checks if the user has admin privileges
    private function hasAdminPrivileges()
    {
        return auth()->user()->hasAnyRole([
            RolesEnum::LeadPool,
            RolesEnum::SeniorManagement,
            RolesEnum::Admin,
            RolesEnum::Engineering,
        ]);
    }

    // Extracts eligible user IDs based on their roles and manager hierarchy
    private function getEligibleUserIds($lob)
    {
        $userIds = $this->walkTree(auth()->user()->id, $lob);

        if (auth()->user()->isManagerORDeputy()) {
            $userIds = UserManager::where('manager_id', auth()->user()->id)
                ->get()
                ->filter(function ($user) use ($userIds) {
                    return in_array($user->user_id, $userIds);
                })
                ->pluck('user_id')
                ->toArray();
        }

        return $userIds;
    }

    private function getBindings(string $table)
    {
        return [
            ':table' => $table,
            ':IMCRM' => LeadSourceEnum::IMCRM,
        ];
    }

    private function addSelect($query, $table)
    {
        $bindings = $this->getBindings($table);

        $query->addSelect(
            DB::raw(strtr(
                '(SUM(CASE WHEN (:table.assignment_type = 1 OR :table.assignment_type = 2) AND :table.advisor_id IS NOT NULL THEN 1 ELSE 0 END) +
                SUM(CASE WHEN (:table.assignment_type = 3 OR :table.assignment_type = 4) AND :table.advisor_id IS NOT NULL THEN 1 ELSE 0 END) +
                SUM(CASE WHEN :table.advisor_id IS NULL THEN 1 ELSE 0 END)) AS received_leads',
                $bindings
            )),
            DB::raw(strtr(
                'SUM(CASE WHEN :table.source = ":IMCRM" THEN 1 ELSE 0 END) AS lead_created,
                COUNT(*) AS total_leads',
                $bindings
            )),
            DB::raw(strtr(
                'SUM(CASE WHEN (:table.assignment_type = 1 OR :table.assignment_type = 2) AND :table.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS auto_assigned',
                $bindings
            )),
            DB::raw(strtr(
                'SUM(CASE WHEN (:table.assignment_type = 3 OR :table.assignment_type = 4) AND :table.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS manually_assigned',
                $bindings
            )),
            DB::raw(strtr(
                'SUM(CASE WHEN :table.advisor_id IS NULL THEN 1 ELSE 0 END) AS unassigned_leads',
                $bindings
            ))
        );
    }

    private function getPersonalQuoteQuery($lob)
    {
        $lobId = $this->getLobId($lob);
        $parentTeam = $this->getProductByName($lob);

        $personalQuoteQuery = PersonalQuote::query()
            ->select('teams.name AS team_name')
            ->leftJoin('user_team', 'user_team.user_id', '=', 'personal_quotes.advisor_id')
            ->leftJoin('teams', 'teams.id', '=', 'user_team.team_id')
            ->whereNotIn('personal_quotes.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('personal_quotes.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->where('personal_quotes.quote_type_id', $lobId)
            ->where('teams.parent_team_id', $parentTeam->id)
            ->groupBy('teams.name')
            ->orderBy('teams.name');

        // Apply user-specific filters if necessary
        if (! $this->hasAdminPrivileges()) {
            $userIds = $this->getUserIdsForAccess($lob);
            $personalQuoteQuery->whereIn('personal_quotes.advisor_id', $userIds);
        }

        $this->addSelect($personalQuoteQuery, 'personal_quotes');

        return $personalQuoteQuery;
    }

    private function getLobId($lob)
    {
        $mappedLob = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE])
            ? quoteTypeCode::Business
            : $lob;

        return QuoteTypeRepository::where('code', $mappedLob)->value('id');
    }

    private function getUserIdsForAccess($lob)
    {
        $userIds = $this->walkTree(auth()->user()->id, $lob);

        if (auth()->user()->isManagerORDeputy()) {
            $userIds = UserManager::where('manager_id', auth()->user()->id)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();
        }

        return $userIds;
    }

    public function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

        $lobs = $this->getUserProducts(auth()->user()->id)
            ->pluck('name', 'name')
            ->toArray();

        return [
            'lob' => $lobs,
            'maxDays' => $maxDays,
            'assignmentTypes' => AssignmentTypeEnum::withLabels()
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

    public function applyFilters($query, $filters, $isPopup = false)
    {
        $filters = (object) $filters;
        $lob = $filters->lob ?? '';
        [$freshLoad, $startDate, $endDate] = $this->getStartAndEndDate($filters, 'createdAtDates');

        $query->when(in_array($lob, [quoteTypeCode::Travel, quoteTypeCode::Health]), function ($q) use ($lob) {
            $segmentMap = [
                quoteTypeCode::Travel => QuoteTypeId::Travel,
                quoteTypeCode::Health => QuoteTypeId::Health,
            ];
            $q->filterBySegment(request()->segment_filter, $segmentMap[$lob]);
        })
            ->when($freshLoad || isset($filters->createdAtDates), function ($q) use ($startDate, $endDate) {
                $q->whereBetween('personal_quotes.created_at', [$startDate, $endDate]);
            })
            ->when(isset($filters->assignmentTypes) && $filters->assignmentTypes !== 'All', function ($q) use ($filters) {
                $q->where('personal_quotes.assignment_type', $filters->assignmentTypes);
            });

        // Map LOBs to their respective table and join conditions
        $joinConditions = [
            quoteTypeCode::Health => ['health_quote_request', 'health_quote_request.uuid', 'personal_quotes.uuid'],
            quoteTypeCode::Home => ['home_quote_request', 'home_quote_request.uuid', 'personal_quotes.uuid'],
            quoteTypeCode::Life => ['life_quote_request', 'life_quote_request.uuid', 'personal_quotes.uuid'],
            quoteTypeCode::Travel => ['travel_quote_request', 'travel_quote_request.uuid', 'personal_quotes.uuid'],
            quoteTypeCode::Pet => ['pet_quote_request', 'pet_quote_request.uuid', 'personal_quotes.uuid'],
            quoteTypeCode::Bike => ['bike_quote_request', 'bike_quote_request.uuid', 'personal_quotes.uuid'],
            quoteTypeCode::Yacht => ['yacht_quote_request', 'yacht_quote_request.uuid', 'personal_quotes.uuid'],
            quoteTypeCode::Cycle => ['cycle_quote_request', 'cycle_quote_request.personal_quote_id', 'personal_quotes.id'],
            quoteTypeCode::Jetski => ['jetski_quote_request', 'jetski_quote_request.personal_quote_id', 'personal_quotes.id'],
            quoteTypeCode::Business => ['business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid'],
        ];

        // Apply join based on LOB
        $query->when(array_key_exists($lob, $joinConditions), function ($q) use ($lob, $joinConditions) {
            [$table, $foreignKey, $localKey] = $joinConditions[$lob];
            $q->join($table, $foreignKey, $localKey);

            if (in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE])) {
                $q->where('business_quote_request.business_type_of_insurance_id',
                    quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            }
        });

        return $query;
    }
    public function applyFiltersForCar($query, $filters, $isPopup = false)
    {
        $filters = (object) $filters;

        [$freshLoad, $startDate, $endDate] = $this->getStartAndEndDate($filters, 'createdAtDates');

        $query->filterBySegment()
            ->when($freshLoad || ! empty($filters->createdAtDates), function ($q) use ($startDate, $endDate) {
                $q->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
            })
            ->when(! empty($filters->assignmentTypes) && $filters->assignmentTypes !== 'All', function ($q) use ($filters) {
                $q->where('car_quote_request.assignment_type', $filters->assignmentTypes);
            })
            ->when(isset($filters->isCommercial) && $filters->isCommercial !== 'All', function ($q) use ($filters) {
                $q->where('car_model.is_commercial', $filters->isCommercial === 'true');
            })
            ->when(! empty($filters->sic_advisor_requested) && $filters->sic_advisor_requested !== 'All', function ($q) use ($filters) {
                $q->where('car_quote_request.sic_advisor_requested', $filters->sic_advisor_requested);
            });

        return $query;
    }
}
