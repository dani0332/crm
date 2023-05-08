<?php

namespace App\Repositories;

use App\Models\VehicleType;

class VehicleTypeRepository extends BaseRepository
{
    public function model()
    {
        return VehicleType::class;
    }
}
