<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;
    protected $table = 'claims';
    public $timestamps = false;

    public function typeofinsurance()
    {
        return $this->belongsTo(TypeOfInsurance::class,'typeofinsurance_id','id');
    }
    public function subtypeofinsurance()
    {
        return $this->belongsTo(SubTypeOfInsurance::class,'subtypeofinsurance_id','id');
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
        return $this->belongsTo(ClaimsStatus::class,'claimsstatus_id','id');
    }
    public function carrepaircoverage()
    {
        return $this->belongsTo(CarRepairCoverage::class,'carrepaircoverage_id','id');
    }
    public function carrepairtype()
    {
        return $this->belongsTo(CarRepairType::class,'carrepairtype_id','id');
    }
    public function rentacar()
    {
        return $this->belongsTo(RentACar::class,'rentacar_id','id');
    }
    public function assignedto()
    {
        return $this->belongsTo(User::class,'assigned_to_id','id');
    }
}
