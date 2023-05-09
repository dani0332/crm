<?php

namespace App\Repositories;

use App\Models\LifeChildren;

class LifeChildrenRepository extends BaseRepository
{
    public function model()
    {
        return LifeChildren::class;
    }
}
