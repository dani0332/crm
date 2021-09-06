<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class CarQuoteInsuranceCoverage extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_insurance_coverage';
    public $access = [
        'write' => ['advisor', 'admin'],
        'update' => ['advisor', 'admin'],
        'delete' => ['advisor', 'admin'],
        'access' => [
            "pa" => [ ],
            "invoicing" => [],
            "advisor" => [ 'car_quote_id','start_date', 'insurance_company_id', 'insurance_plan_id','excess','ancillary_excess','premium_price','personal_accident_benefit','breakdown_recovery','off_road_cover','rend_a_car','geographical_area','vehicle_type_id','repair_type','financed_by'],
            "admin" => [ 'car_quote_id','start_date', 'insurance_company_id', 'insurance_plan_id','excess','ancillary_excess','premium_price','personal_accident_benefit','breakdown_recovery','off_road_cover','rend_a_car','geographical_area','vehicle_type_id','repair_type','financed_by'],
        ],
        "list" => [
            "pa" => ['id','start_date', 'insurance_company_id','insurance_plan_id', 'excess', 'ancillary_excess','premium_price','personal_accident_benefit','breakdown_recovery','off_road_cover','rend_a_car','geographical_area','vehicle_type_id','repair_type','financed_by'],
            "invoicing" => ['id','start_date', 'insurance_company_id','insurance_plan_id', 'excess', 'ancillary_excess','premium_price','personal_accident_benefit','breakdown_recovery','off_road_cover','rend_a_car','geographical_area','vehicle_type_id','repair_type','financed_by'],
            "advisor" => ['id','start_date', 'insurance_company_id','insurance_plan_id', 'excess', 'ancillary_excess','premium_price','personal_accident_benefit','breakdown_recovery','off_road_cover','rend_a_car','geographical_area','vehicle_type_id','repair_type','financed_by'],
            "admin" => ['id','start_date', 'insurance_company_id','insurance_plan_id', 'excess', 'ancillary_excess','premium_price','personal_accident_benefit','breakdown_recovery','off_road_cover','rend_a_car','geographical_area','vehicle_type_id','repair_type','financed_by']
        ]
    ];


    public function insurance_company_id()
    {
        return $this->hasOne(InsuranceCompany::class, 'id', 'insurance_company_id');
    }

    public function insurance_plan_id()
    {
        return $this->hasOne(CarQuoteInsurancePlan::class, 'id', 'insurance_plan_id');
    }

    public function vehicle_type_id()
    {
        return $this->hasOne(VehicleType::class, 'id', 'vehicle_type_id');
    }

    public function relations() {

        if($this->isGetList)
            return ['insurance_company_id' , 'insurance_plan_id', 'vehicle_type_id'];
        else
            return ['insurance_company_id' , 'insurance_plan_id', 'vehicle_type_id'];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }
}
