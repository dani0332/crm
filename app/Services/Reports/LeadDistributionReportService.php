<?php

namespace App\Services\Reports;

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
        if (empty($lob)) {
            return [];
        }

        $filters = [
            'lob' => $request->lob,
            'createdAtDates' => $request->createdAtDates,
            'assignmentTypes' => $request->assignmentTypes,
            'segment_filter' => $request->segment_filter,
            'sic_advisor_requested' => $request->sic_advisor_requested,
            'page' => $request->page,
        ];

        if ($lob === quoteTypeCode::Car) {
            $query = $this->getCarQuoteQuery($lob);
            $query = $this->applyFiltersForCar($query, $filters);
        } else {
            $query = $this->getPersonsalQuoteQuery($lob);
            $query = $this->applyFilters($query, $filters);
        }

        return $query->paginate(15)
            ->withQueryString();
    }

    private function getCarQuoteQuery($lob)
    {
        $query = CarQuote::query()
            ->select(
                'teams.name AS team_name',
            )
            ->leftJoin('user_team', 'user_team.user_id', 'car_quote_request.advisor_id')
            ->leftJoin('teams', 'teams.id', 'user_team.team_id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('teams.name')
            ->orderBy('teams.name');

        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            $query->where('car_quote_request.advisor_id', auth()->user()->id);
        } elseif (
            ! auth()->user()->hasAnyRole([
                RolesEnum::LeadPool,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ])
        ) {
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

            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        }

        $this->addSelect($query, 'car_quote_request');

        return $query;
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
        $query->addSelect(
            DB::raw(
                strtr('(SUM(CASE WHEN :table.auto_assigned = 1 AND :table.advisor_id IS NOT NULL THEN 1 ELSE 0 END)
                        + SUM(CASE WHEN :table.auto_assigned = 0 AND :table.advisor_id IS NOT NULL THEN 1 ELSE 0 END)
                        + SUM(CASE WHEN :table.advisor_id IS NULL THEN 1 ELSE 0 END)) AS received_leads', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.source = ":IMCRM" THEN 1 ELSE 0 END) AS lead_created, COUNT(*) AS total_leads', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.auto_assigned = 1 AND :table.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS auto_assigned', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.auto_assigned = 0 AND :table.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS manually_assigned', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.advisor_id IS NULL THEN 1 ELSE 0 END) AS unassigned_leads', $this->getBindings($table))
            ),
        );
    }

    private function getPersonsalQuoteQuery($lob)
    {
        $lobFiltered = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE]) ? quoteTypeCode::Business : $lob;
        $lobId = QuoteTypeRepository::where('code', $lobFiltered)->first();

        $query = PersonalQuote::query()
            ->select(
                'teams.name AS team_name',
            )
            ->leftJoin('user_team', 'user_team.user_id', 'personal_quotes.advisor_id')
            ->leftJoin('teams', 'teams.id', 'user_team.team_id')
            ->whereNotIn('personal_quotes.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('personal_quotes.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->where('personal_quotes.quote_type_id', $lobId->id)
            ->groupBy('teams.name')
            ->orderBy('teams.name');

        if (
            ! auth()->user()->hasAnyRole([
                RolesEnum::LeadPool,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ])
        ) {
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

            $query = $query->whereIn('personal_quotes.advisor_id', $userIds);
        }

        $this->addSelect($query, 'personal_quotes');

        return $query;
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
            ->when(isset($filters->assignmentTypes) && $filters->assignmentTypes != 'All', function ($q) use ($filters) {
                $q->where('personal_quotes.assignment_type', $filters->assignmentTypes);
            })
            ->when($lob === quoteTypeCode::Health, function ($q) {
                $q->join('health_quote_request', 'health_quote_request.uuid', 'personal_quotes.uuid');
            })
            ->when($lob === quoteTypeCode::Home, function ($q) {
                $q->join('home_quote_request', 'home_quote_request.uuid', 'personal_quotes.uuid');
            })
            ->when($lob === quoteTypeCode::Life, function ($q) {
                $q->join('life_quote_request', 'life_quote_request.uuid', 'personal_quotes.uuid');
            })
            ->when(in_array($lob, [quoteTypeCode::Business, quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE]), function ($q) use ($lob) {
                $q->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid')
                    ->when(in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE]), function ($q) {
                        $q->where('business_quote_request.business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
                    });
            })
            ->when($lob === quoteTypeCode::Travel, function ($q) {
                $q->join('travel_quote_request', 'travel_quote_request.uuid', 'personal_quotes.uuid');
            })
            ->when($lob === quoteTypeCode::Pet, function ($q) {
                $q->join('pet_quote_request', 'pet_quote_request.uuid', 'personal_quotes.uuid');
            })
            ->when($lob === quoteTypeCode::Bike, function ($q) {
                $q->join('bike_quote_request', 'bike_quote_request.uuid', 'personal_quotes.uuid');
            })
            ->when($lob === quoteTypeCode::Yacht, function ($q) {
                $q->join('yacht_quote_request', 'yacht_quote_request.uuid', 'personal_quotes.uuid');
            })
            ->when($lob === quoteTypeCode::Cycle, function ($q) {
                $q->join('cycle_quote_request', 'cycle_quote_request.personal_quote_id', 'personal_quotes.id');
            })
            ->when($lob === quoteTypeCode::Jetski, function ($q) {
                $q->join('jetski_quote_request', 'jetski_quote_request.personal_quote_id', 'personal_quotes.id');
            });

        return $query;
    }

    public function applyFiltersForCar($query, $filters, $isPopup = false)
    {
        $filters = (object) $filters;

        [$freshLoad, $startDate, $endDate] = $this->getStartAndEndDate($filters, 'createdAtDates');

        $query->filterBySegment()
            ->when($freshLoad || isset($filters->createdAtDates), function ($q) use ($startDate, $endDate) {
                $q->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
            })
            ->when(isset($filters->assignmentTypes) && $filters->assignmentTypes != 'All', function ($q) use ($filters) {
                $q->where('car_quote_request.assignment_type', $filters->assignmentTypes);
            })
            ->when(isset($filters->isCommercial) && $filters->isCommercial != 'All', function ($q) use ($filters) {
                $filters->isCommercial = $filters->isCommercial == 'true';
                $q->where('car_model.is_commercial', $filters->isCommercial);
            })
            ->when(isset($filters->sic_advisor_requested) && $filters->sic_advisor_requested != 'All', function ($q) use ($filters) {
                $q->where('car_quote_request.sic_advisor_requested', '=', $filters->sic_advisor_requested);
            });

        return $query;
    }
}
