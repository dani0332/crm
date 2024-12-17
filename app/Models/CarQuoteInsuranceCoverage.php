<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarQuoteInsuranceCoverage extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_insurance_coverage';
    public $access = [
        'write' => ['advisor', 'admin', 'oe'],
        'update' => ['advisor', 'admin', 'oe'],
        'delete' => ['advisor', 'admin', 'oe'],
        'access' => [
            'pa' => [],
            'production_approval_manager' => [],
            'invoicing' => [],
            'payment' => [],
            'advisor' => ['car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
            'oe' => ['car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
            'admin' => ['car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
        ],
        'list' => [
            'pa' => ['id', 'car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
            'production_approval_manager' => ['id', 'car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
            'invoicing' => ['id', 'car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
            'payment' => ['id', 'car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
            'advisor' => ['id', 'car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
            'oe' => ['id', 'car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
            'admin' => ['id', 'car_quote_id', 'start_date', 'insurance_company_id', 'insurance_plan_id', 'excess', 'sum_insured', 'ancillary_excess', 'premium_price', 'personal_accident_benefit', 'breakdown_recovery', 'off_road_cover', 'rend_a_car', 'geographical_area', 'vehicle_type_id', 'repair_type', 'financed_by'],
        ],
    ];

    public function insurance_company_id()
    {
        return $this->hasOne(InsuranceProvider::class, 'id', 'insurance_company_id');
    }

    public function insurance_plan_id()
    {
        return $this->hasOne(CarPlan::class, 'id', 'insurance_plan_id');
    }

    public function vehicle_type_id()
    {
        return $this->hasOne(VehicleType::class, 'id', 'vehicle_type_id');
    }

    public function car_quote_id()
    {
        return $this->hasOne(CarQuote::class, 'id', 'car_quote_id')->select(['id', 'quote_status_id', 'plan_id']);
    }

    public function car_quote_request_add_on()
    {
        return $this->hasMany(CarQuoteRequestAddOn::class, 'quote_request_id', 'car_quote_id')->select(['id', 'quote_request_id', 'addon_option_id', 'price']);
    }

    public function relations()
    {
        if ($this->isGetList) {
            return ['insurance_company_id', 'insurance_plan_id', 'vehicle_type_id', 'car_quote_id.quote_status_id', 'car_quote_id.plan_id', 'car_quote_id.plan_id.provider_id', 'car_quote_request_add_on', 'car_quote_request_add_on.addon_option_id', 'car_quote_request_add_on.addon_option_id.addon_id'];
        } else {
            return ['insurance_company_id', 'insurance_plan_id', 'vehicle_type_id', 'car_quote_id.quote_status_id', 'car_quote_id.plan_id'];
        }
    }

    public function saveForm($request, $update = false)
    {
        $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1), 'advisor_id' => Auth::user()->id])->first();

        $request->request->add(['insurance_company_id' => $request->input('insurance_company', 0)]);
        $request->request->add(['insurance_plan_id' => $request->input('plan_id', 0)]);

        if ($carQuote && parent::saveForm($request, $update)) {
            $carQuote->plan_id = $request->input('plan_id');

            return $carQuote->save();
        }

        return $this->APIController->respondData(['message' => 'Something wrong'], 500);
    }
}
