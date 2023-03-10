<?php

namespace App\Repositories;

use App\Models\YearOfManufacture;

class YearOfManufactureRepository extends BaseRepository
{
    public function model()
    {
        return YearOfManufacture::class;
    }
}
