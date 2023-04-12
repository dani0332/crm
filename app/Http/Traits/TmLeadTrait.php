<?php

namespace App\Http\Traits;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarTypeInsurance;
use App\Models\Emirate;
use App\Models\Nationality;
use App\Models\TmInsuranceType;
use App\Models\TmLeadStatus;
use App\Models\TmLeadType;
use App\Models\UAELicenseHeldFor;

trait TmLeadTrait
{
    public function getInsuranceTypes()
    {
        return TmInsuranceType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }

    public function getLeadTypes()
    {
        return TmLeadType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }

    public function getLeadStatuses()
    {
        return TmLeadStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }

    public function getNationalities()
    {
        return Nationality::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }

    public function getYearsOfDriving()
    {
        return UAELicenseHeldFor::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }

    public function getCarMakes()
    {
        return CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }

    public function getCarModels()
    {
        return CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }

    public function getEmiratesOfRegistrations()
    {
        return Emirate::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }

    public function getCarTypeInsurances()
    {
        return CarTypeInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }
}
