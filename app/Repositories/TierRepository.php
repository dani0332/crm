<?php

namespace App\Repositories;

use App\Models\Tier;

class TierRepository extends BaseRepository
{
    public function model()
    {
        return Tier::class;
    }
}
