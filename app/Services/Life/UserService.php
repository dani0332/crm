<?php

namespace App\Services\Life;

use App\Enums\QuoteTypes;
use App\Models\User;
use App\Services\BaseService;

class UserService extends BaseService
{
    public function getPersonalQuoteAdvisors($modelType)
    {
        if ($modelType == QuoteTypes::PET->value) {
            $roles = [strtoupper($modelType).'_ADVISOR', strtoupper($modelType).'_RENEWAL_ADVISOR', strtoupper($modelType).'_NEW_BUSINESS_ADVISOR'];
        } elseif ($modelType == QuoteTypes::CAR->value) {
            $roles = [strtoupper($modelType).'_ADVISOR', strtoupper($modelType).'_DEPUTY_MANAGER'];
        } else {
            $roles = [strtoupper($modelType).'_ADVISOR'];
        }

        return User::with(['roles' => fn ($q) => $q->whereIn('name', $roles)])
            ->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);  //todo: add required roles here
            })->get();
    }
}
