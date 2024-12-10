<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
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
use Illuminate\Support\Facades\Auth;
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
        // dd($query->toSql(), $query->getBindings());
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

    public function getFiltersByLob()
    {
        $canView = [
            quoteTypeCode::Car => ! Auth::user()->hasRole(RolesEnum::CarAdvisor),
            quoteTypeCode::Bike => ! Auth::user()->hasRole(RolesEnum::BikeAdvisor),
            quoteTypeCode::Health => ! Auth::user()->hasRole(RolesEnum::RMAdvisor),
            quoteTypeCode::Travel => ! Auth::user()->hasRole(RolesEnum::TravelAdvisor),
            quoteTypeCode::Pet => ! Auth::user()->hasRole(RolesEnum::PetAdvisor),
            quoteTypeCode::Cycle => ! Auth::user()->hasRole(RolesEnum::CycleAdvisor),
            quoteTypeCode::Yacht => ! Auth::user()->hasRole(RolesEnum::YachtAdvisor),
            quoteTypeCode::Life => ! Auth::user()->hasRole(RolesEnum::LifeAdvisor),
            quoteTypeCode::Home => ! Auth::user()->hasRole(RolesEnum::HomeAdvisor),
            quoteTypeCode::CORPLINE => ! Auth::user()->hasRole(RolesEnum::CorpLineAdvisor),
            quoteTypeCode::GroupMedical => ! Auth::user()->hasRole(RolesEnum::GMAdvisor),
        ];

        return [
            'advisors' => [
                'can_view' => $canView,
            ],
            'teams' => [
                'can_view' => $canView,
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::Health,
                    quoteTypeCode::CORPLINE,
                    quoteTypeCode::GroupMedical,
                ],
            ],
            'sub_teams' => [
                'can_view' => $canView,
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::GroupMedical,
                ],
            ],
            'tiers' => [
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::Bike,
                ],
            ],
            'is_ecommerce' => [
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::Bike,
                    quoteTypeCode::Health,
                    quoteTypeCode::Travel,
                ],
            ],
            'vehicle_type' => [
                'lobs' => [
                    quoteTypeCode::Car,
                ],
            ],
            'isCommercial' => [
                'lobs' => [
                    quoteTypeCode::Car,
                ],
            ],
            'isEmbeddedProducts' => [
                'lobs' => [
                    quoteTypeCode::Travel,
                ],
            ],
            'insurance_type' => [
                'lobs' => [
                    quoteTypeCode::Travel,
                    quoteTypeCode::Life,
                    quoteTypeCode::CORPLINE,
                ],
            ],
            'insurance_for' => [
                'lobs' => [
                    quoteTypeCode::Health,
                    quoteTypeCode::Home,
                ],
            ],
            'travel_coverage' => [
                'lobs' => [
                    quoteTypeCode::Travel,
                ],
            ],
            'segment_filter' => [
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::Health,
                    quoteTypeCode::Travel,
                ],
            ],
        ];
    }

    public function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

        $lobs = $this->getLobByPermissions();

        return [
            'lob' => $lobs,
            'maxDays' => $maxDays
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

        $query->when($lob === quoteTypeCode::Travel, function ($q) {
                $q->filterBySegment(request()->segment_filter, QuoteTypeId::Travel);
            })
            ->when($lob === quoteTypeCode::Health, function ($q) {
                $q->filterBySegment(request()->segment_filter, QuoteTypeId::Health);
            })
            ->when($lob === quoteTypeCode::Car, function ($q) {
                $q->filterBySegment(request()->segment_filter, QuoteTypeId::Car);
            })
            ->when($freshLoad || isset($filters->createdAtDates), function ($q) use ($startDate, $endDate) {
                $q->whereBetween('personal_quotes.created_at', [$startDate, $endDate]);
            })
            ->when(isset($filters->assignmentTypes) && $filters->assignmentTypes != 'All', function ($q) use ($filters) {
                $q->where('personal_quotes.assignment_type', $filters->assignmentTypes);
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

    public function getLobByPermissions()
    {
        $lobs = [
            quoteTypeCode::Car => PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW,
            quoteTypeCode::Bike => PermissionsEnum::BIKE_DISTRIBUTION_REPORT,
            quoteTypeCode::Health => PermissionsEnum::HEALTH_DISTRIBUTION_REPORT,
            quoteTypeCode::Travel => PermissionsEnum::TRAVEL_DISTRIBUTION_REPORT,
            quoteTypeCode::Pet => PermissionsEnum::PET_DISTRIBUTION_REPORT,
            quoteTypeCode::Cycle => PermissionsEnum::CYCLE_DISTRIBUTION_REPORT,
            quoteTypeCode::Yacht => PermissionsEnum::YACHT_DISTRIBUTION_REPORT,
            quoteTypeCode::Life => PermissionsEnum::LIFE_DISTRIBUTION_REPORT,
            quoteTypeCode::Home => PermissionsEnum::HOME_DISTRIBUTION_REPORT,
        ];

        $lobs = array_filter($lobs, function ($permission) {
            return Auth::user()->can($permission);
        });

        $lobs = QuoteTypeRepository::GetList()
            ->filter(function ($lob) use ($lobs) {
                return array_key_exists($lob->code, $lobs);
            })
            ->pluck('code', 'text')
            ->toArray();

        if (Auth::user()->can(PermissionsEnum::CORPLINE_DISTRIBUTION_REPORT)) {
            $lobs = array_merge(['CorpLine Insurance' => quoteTypeCode::CORPLINE], $lobs);
        }

        if (Auth::user()->can(PermissionsEnum::GROUPMEDICAL_DISTRIBUTION_REPORT)) {
            $lobs = array_merge(['Group Medical Insurance' => quoteTypeCode::GroupMedical], $lobs);
        }

        return $lobs;
    }
}
