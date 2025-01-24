<?php

namespace App\Repositories;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
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
        } else {
            $roles = [strtoupper($modelType).'_ADVISOR'];
        }

        return $this->with(['roles' => fn ($q) => $q->whereIn('name', $roles)])
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

        $existingRoles = Role::whereIn('name', $roles)->pluck('name')->toArray();

        return User::role($existingRoles)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();
    }
}
