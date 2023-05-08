<?php

namespace App\Repositories;

use App\Models\MartialStatus;

class MaritalStatusRepository extends BaseRepository
{
    public function model()
    {
        return MartialStatus::class;
    }
}
