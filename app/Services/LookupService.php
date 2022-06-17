<?php

namespace App\Services;

use App\Models\VehicleType;
use App\Models\YearOfManufacture;

class LookupService extends BaseService
{

    public function getYearsOfManufacture()
	{
        return YearOfManufacture::select('text as id', 'text')->get();
	}

	public function getVehicleTypes()
    {
        return VehicleType::select('id', 'text')->where("is_active", true)->get();
    }

}
