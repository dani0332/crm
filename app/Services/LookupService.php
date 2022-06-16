<?php

namespace App\Services;

use App\Models\VehicleType;
use App\Models\YearOfManufacture;

class LookupService extends BaseService
{

    public function getYearsOfManufacture()
	{
        $yearsOfManufacture = YearOfManufacture::select('text as id', 'text')->get();

        return $yearsOfManufacture;
	}

	public function getVehicleTypes()
    {
        $vehicleTypes = VehicleType::select('id', 'text')->where("is_active", true)->get();

        return $vehicleTypes;
    }

}
