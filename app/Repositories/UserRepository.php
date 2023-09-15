<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Models\Team;
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
        } elseif ($modelType == QuoteTypes::CAR->value) {
            $roles = [strtoupper($modelType).'_ADVISOR', strtoupper($modelType).'_DEPUTY_MANAGER'];
        } else {
            $roles = [strtoupper($modelType).'_ADVISOR'];
        }

        return $this->with(['roles' => fn ($q) => $q->whereIn('name', $roles)])
            ->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);  //todo: add required roles here
            })->get();
    }

    /**
     * @param $teamName this could be a string - single team or array of team names
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
}
