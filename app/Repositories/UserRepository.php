<?php

namespace App\Repositories;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\DB;

class UserRepository extends BaseRepository
{
    use TeamHierarchyTrait;
    public function model()
    {
        return User::class;
    }

    public function fetchGetList($modelType)
    {
        $advisorType = strtoupper(explode('/', request()->path())[1]);

        if (auth()->user()->isRenewalUser() || auth()->user()->isRenewalManager() || auth()->user()->isRenewalAdvisor()) {
            $advisorType = $advisorType.'_RENEWAL';
        }

        if (auth()->user()->isNewBusinessManager() || auth()->user()->isNewBusinessAdvisor()) {
            $advisorType = $advisorType.'_NEW_BUSINESS_';
        }

        $query = $this->join('model_has_roles as mr', 'mr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->select('users.id as id', DB::raw("CONCAT(users.name,' - ',r.name) AS name"));

        switch (strtolower($modelType)) {
            case strtolower(quoteTypeCode::Car):
                $query->whereIn(
                    'r.name',
                    [
                        RolesEnum::CarAdvisor,
                    ]
                );

            case strtolower(quoteTypeCode::Health):
                $query->whereIn('r.name', [
                    RolesEnum::RMAdvisor,
                    RolesEnum::EBPAdvisor,
                    RolesEnum::HealthRenewalAdvisor,
                ]);

            case strtolower(quoteTypeCode::Business):
                $query->whereIn('r.name', [
                    RolesEnum::CorpLineAdvisor,
                    RolesEnum::CorpLineRenewalAdvisor,
                    RolesEnum::GMRenewalAdvisor,
                ]);

            default:
                $query->whereIn('r.name', [
                    strtoupper($advisorType).'_ADVISOR',
                    strtoupper($advisorType).'_RENEWAL_ADVISOR',
                    strtoupper($advisorType).'_NEW_BUSINESS_ADVISOR',
                    strtoupper($advisorType).'_DEPUTY_MANAGER',
                ]);
        }

        return $query->orderBy('r.name')->distinct()->get();
    }

    public function fetchGetPersonalQuoteAdvisors($modelType)
    {
        if ($modelType == QuoteTypes::PET->value) {
            $roles = [strtoupper($modelType).'_ADVISOR', strtoupper($modelType).'_RENEWAL_ADVISOR', strtoupper($modelType).'_NEW_BUSINESS_ADVISOR'];
        } elseif ($modelType == QuoteTypes::CAR->value) {
            $roles = [strtoupper($modelType).'_ADVISOR', strtoupper($modelType).'_DEPUTY_MANAGER'];
        } elseif ($modelType == QuoteTypes::SAVINGS->value) {
            $roles = [strtoupper($modelType).'_ADVISOR', strtoupper($modelType).'_MANAGER'];
        } else {
            $roles = [strtoupper($modelType).'_ADVISOR'];
        }

        return $this->with(['roles'])
            ->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);  // todo: add required roles here
            })->get();
    }

    /**
     * @param  $teamName  this could be a string - single team or array of team names
     * @return mixed
     */
    public function fetchIsUserMemberofTeam($userId, $teamName)
    {
        $teamName = (! is_array($teamName)) ? [$teamName] : $teamName;

        $teams = Team::whereIn('name', $teamName)->get();

        return $this->where('id', $userId)->whereHas('teams', function ($q) use ($teams) {
            if ($teams) {
                $q->whereIn('team_id', $teams->pluck('id')->toArray());
            }
        })->first();
    }

    public function fetchAdvisorsList()
    {
        $roles = [
            RolesEnum::CarAdvisor,
            RolesEnum::CarRenewalAdvisor,
            RolesEnum::CarNewBusinessAdvisor,
            RolesEnum::CarRevivalAdvisor,
            RolesEnum::HomeAdvisor,
            RolesEnum::HomeRenewalAdvisor,
            RolesEnum::HealthAdvisor,
            RolesEnum::HealthRenewalAdvisor,
            RolesEnum::LifeAdvisor,
            RolesEnum::LifeRenewalAdvisor,
            RolesEnum::BusinessAdvisor,
            RolesEnum::GMAdvisor,
            RolesEnum::GMRenewalAdvisor,
            RolesEnum::CorpLineAdvisor,
            RolesEnum::CorpLineRenewalAdvisor,
            RolesEnum::BikeAdvisor,
            RolesEnum::YachtAdvisor,
            RolesEnum::YachtNewBusinessAdvisor,
            RolesEnum::YachtRenewalAdvisor,
            RolesEnum::TravelAdvisor,
            RolesEnum::PetRenewalAdvisor,
            RolesEnum::PetAdvisor,
            RolesEnum::CycleAdvisor,
            RolesEnum::CycleNewBusinessAdvisor,
            RolesEnum::CycleRenewalAdvisor,
            RolesEnum::JetskiAdvisor,
            RolesEnum::RMAdvisor,
            RolesEnum::EBPAdvisor,
            RolesEnum::Advisor,
        ];

        // Filter to existing roles only
        $existingRoles = Role::whereIn('name', $roles)->pluck('name')->toArray();

        // Use Spatie's whereHas method which builds a single efficient query
        return User::query()
            ->whereHas('roles', function ($query) use ($existingRoles) {
                $query->whereIn('name', $existingRoles);
            })
            ->where('is_active', 1)
            ->select('name', 'id')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function fetchSupportUserList()
    {
        $roles = [RolesEnum::CLIENTSUPPORT];

        // Filter to existing roles only
        $existingRoles = Role::whereIn('name', $roles)->pluck('name')->toArray();
        $lineOfBusinessFilter = is_array(request('line_of_business')) ? request('line_of_business') : [request('line_of_business')];
        // getting 'product codes' from QuoteTypes
        $productCodes = [];
        foreach ($lineOfBusinessFilter as $lineOfBusinessItem) {
            $quoteTypeName = QuoteTypes::getName($lineOfBusinessItem);
            if (! empty($quoteTypeName)) {
                $productCodes[] = $quoteTypeName;
            }
        }

        $query = User::query()
            ->whereHas('roles', function ($query) use ($existingRoles) {
                $query->whereIn('name', $existingRoles);
            })
            ->where('is_active', 1);

        // 1. Department filter (users.department_id)
        $departmentFilter = request('department');
        if (! empty($departmentFilter)) {
            $query->whereIn('department_id', (array) $departmentFilter);
        }

        // 2. Line of Business filter (via user_products -> teams where type = PRODUCT)
        if (! empty($productCodes)) {

            $query->whereHas('products', function ($q) use ($productCodes) {
                $q->where('type', TeamTypeEnum::PRODUCT)
                    ->whereIn('teams.code', (array) $productCodes);
            });
        }

        // 3. Business Insurance Type filter (via business_type_of_insurance_user pivot table)
        $businessInsuranceTypeFilter = request('business_insurance_type');
        if (! empty($businessInsuranceTypeFilter)) {
            $query->whereHas('businessTypes', function ($q) use ($businessInsuranceTypeFilter) {
                $q->whereIn('business_type_of_insurance.id', (array) $businessInsuranceTypeFilter);
            });
        }

        $supportUsers = $query->select('name', 'id')
            ->orderBy('name')
            ->get()
            ->toArray();

        // logger()->debug("fetchSupportUserList toRawSql: " . $query->toRawSql());

        return $supportUsers;
    }
}
