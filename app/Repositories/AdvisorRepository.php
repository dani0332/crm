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
                    strtoupper($modelType).'_ADVISOR',
                    strtoupper($modelType).'_RENEWAL_ADVISOR',
                    strtoupper($modelType).'_NEW_BUSINESS_ADVISOR'
                ]);
        }

        return $query->orderBy('r.name')->distinct()->get();
    }
}
