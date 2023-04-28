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
