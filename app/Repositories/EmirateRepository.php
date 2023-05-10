<?php

namespace App\Repositories;


use App\Models\Emirate;

class EmirateRepository extends BaseRepository
{
    public function model()
    {
        return Emirate::class;
    }
}
