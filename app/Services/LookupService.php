<?php

namespace App\Services;

use App\Models\VehicleType;
use App\Models\YearOfManufacture;
use App\Models\CarModelDetail;
use App\Models\UAELicenseHeldFor;
use App\Models\MemberCategory;
use App\Models\SalaryBand;

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

    public function getTrimListByCarModel($id)
    {
        return CarModelDetail::select('id', 'text')->where("is_active", true)->where("car_model_id", $id)->get();
    }

    public function getBackHomeLicensed()
    {
        return UAELicenseHeldFor::isBackHomeActive()->get();
    }

    public function getMemberCategories()
    {
        return MemberCategory::active()->get();
    }

    public function getSalaryBands()
    {
        return SalaryBand::active()->get();
    }

}
