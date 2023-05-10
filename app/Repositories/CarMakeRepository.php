<?php

namespace App\Repositories;


use App\Models\CarMake;

class CarMakeRepository extends BaseRepository
{
    public function model()
    {
        return CarMake::class;
    }
}
