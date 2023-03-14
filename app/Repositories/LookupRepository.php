<?php

namespace App\Repositories;

use App\Models\Lookup;

class LookupRepository extends BaseRepository
{
    public function model()
    {
        return Lookup::class;
    }
}
