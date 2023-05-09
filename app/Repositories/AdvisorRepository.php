<?php

namespace App\Repositories;

use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdvisorRepository extends BaseRepository
{
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

        switch (strtolower($modelType)){
            case strtolower(quoteTypeCode::Car):
                $query->whereIn('r.name', [
                    RolesEnum::CarAdvisor,
                    RolesEnum::CarDeputyManager]
                );

            case strtolower(quoteTypeCode::Health):
                $query->whereIn('r.name', [
                    RolesEnum::RMAdvisor,
                    RolesEnum::EBPAdvisor,
                    RolesEnum::HealthRenewalAdvisor,
                    RolesEnum::HealthNewBusinessAdvisor,
                    RolesEnum::HealthWCUAdvisor
                ]);

            case strtolower(quoteTypeCode::Business):
                $query->whereIn('r.name', [
                    RolesEnum::CorpLineAdvisor,
                    RolesEnum::CorpLineRenewalAdvisor,
                    RolesEnum::CorpLineNewBusinessAdvisor,
                    RolesEnum::GMRenewalAdvisor,
                    RolesEnum::GMNewBusinessAdvisor
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
}
