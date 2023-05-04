<?php

namespace App\Repositories;

use App\Models\LifeChildren;

class LifeChildernRepository extends BaseRepository
{
    public function model()
    {
        return LifeChildren::class;
    }
}
