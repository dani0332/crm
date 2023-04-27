<?php

namespace App\Repositories;

use App\Models\LifeChildren;

class ChildernRepository extends BaseRepository
{
    public function model()
    {
        return LifeChildren::class;
    }
}
