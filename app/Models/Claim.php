<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;
    protected $table = 'claims';

    public function typeofinsurance()
    {
        return $this->belongsTo(TypeOfInsurance::class,'type_of_insurances_id','id');
    }
    public function subtypeofinsurance()
    {
        return $this->belongsTo(SubTypeOfInsurance::class,'sub_type_of_insurance_id','id');
    }
    public function carmake()
    {
        return $this->belongsTo(CarMake::class,'car_make_id','id');
    }
    public function carmodel()
    {
        return $this->belongsTo(CarModel::class,'car_model_id','id');
    }
    public function claimsstatus()
    {
        return $this->belongsTo(ClaimsStatus::class,'claims_status_id','id');
    }
    public function carrepaircoverage()
    {
        return $this->belongsTo(CarRepairCoverage::class,'car_repair_coverage_id','id');
    }
    public function carrepairtype()
    {
        return $this->belongsTo(CarRepairType::class,'car_repair_type_id','id');
    }
    public function rentacar()
    {
        return $this->belongsTo(RentACar::class,'rent_a_car_id','id');
    }
    public function assignedto()
    {
        return $this->belongsTo(User::class,'assigned_to_id','id');
    }
    public function createdby()
    {
        return $this->belongsTo(User::class,'created_by_id','id');
    }
    public function modifiedby()
    {
        return $this->belongsTo(User::class,'modified_by_id','id');
    }
}
