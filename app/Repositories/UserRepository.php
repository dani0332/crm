<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Models\User;

class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }

    public function fetchGetPersonalQuoteAdvisors($modelType)
    {
        // $query = User::join('model_has_roles as mr', 'mr.model_id', '=', 'users.id')
        // ->join('roles as r', 'r.id', '=', 'mr.role_id')
        // ->select('users.id', DB::raw("CONCAT(users.name,' - ',r.name) AS name"));
        // if (strtolower($modelType) == strtolower(quoteTypeCode::Car)) {
        //     $query->whereIn('r.name', [RolesEnum::CarAdvisor, RolesEnum::CarDeputyManager]);
        // } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Health)) {
        //     $query->whereIn('r.name', [RolesEnum::RMAdvisor, RolesEnum::EBPAdvisor, RolesEnum::HealthRenewalAdvisor, RolesEnum::HealthNewBusinessAdvisor, RolesEnum::HealthWCUAdvisor]);
        // } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Business)) {
        //     $query->whereIn('r.name', [RolesEnum::CorpLineAdvisor, RolesEnum::CorpLineRenewalAdvisor, RolesEnum::CorpLineNewBusinessAdvisor, RolesEnum::GMRenewalAdvisor, RolesEnum::GMNewBusinessAdvisor]);
        // } else {
        //     $query->whereIn('r.name', [strtoupper($modelType) . '_ADVISOR', strtoupper($modelType) . '_RENEWAL_ADVISOR', strtoupper($modelType) . '_NEW_BUSINESS_ADVISOR']);
        // }
        // return $query->orderBy('r.name')->distinct()->get();

        if ($modelType == QuoteTypes::PET->value) {
            $roles = [strtoupper($modelType).'_ADVISOR', strtoupper($modelType).'_RENEWAL_ADVISOR', strtoupper($modelType).'_NEW_BUSINESS_ADVISOR'];
        } else {
            $roles = [strtoupper($modelType).'_ADVISOR'];
        }

        return $this->with(['roles' => fn ($q) => $q->whereIn('name', $roles)])
            ->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);  //todo: add required roles here
            })->get();
    }
}
