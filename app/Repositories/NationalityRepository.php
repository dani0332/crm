<?php

namespace App\Repositories;

use App\Models\Nationality;

class NationalityRepository extends BaseRepository
{
    public function model()
    {
        return Nationality::class;
    }
}
