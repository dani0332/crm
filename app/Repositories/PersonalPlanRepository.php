<?php

namespace App\Repositories;

use App\Models\PersonalPlan;

class PersonalPlanRepository extends BaseRepository
{
    public function model()
    {
        return PersonalPlan::class;
    }
}
