<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Auth;

class CarQuote extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_request';
    protected $casts = [
        'dob' => 'datetime',
    ];



    public function uaeLicenseHeldFor()
    {
        return $this->hasOne(UAELicenseHeldFor::class, 'id', 'uae_license_held_for_id');
    }

    public function carMake()
    {
        return $this->hasOne(CarMake::class, 'id', 'car_make_id');
    }

    public function carModel()
    {
        return $this->hasOne(CarModel::class, 'id', 'car_model_id');
    }

    public function emirate()
    {
        return $this->hasOne(Emirate::class, 'id', 'emirate_of_registration_id');
    }
    public function claimHistory()
    {
        return $this->hasOne(ClaimHistory::class, 'id', 'claim_history_id');
    }

    public function carTypeInsurance()
    {
        return $this->hasOne(CarTypeInsurance::class, 'id', 'car_type_insurance_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function nationality()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id');
    }

    public function paymentStatus()
    {
        return $this->hasOne(PaymentStatus::class, 'id', 'payment_status_id');
    }

    public function quoteStatus()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }
    public function getCreatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }


    /*****  NewRelationships so old should not effect */


    public function car_make_id()
    {
        return $this->hasOne(CarMake::class, 'id', 'car_make_id')->select(['id', 'code','text']);;
    }

    public function car_model_id()
    {
        return $this->hasOne(CarModel::class, 'id', 'car_model_id')->select(['id', 'code','text']);;
    }

    public function emirate_of_registration_id()
    {
        return $this->hasOne(Emirate::class, 'id', 'emirate_of_registration_id')->select(['id', 'code','text']);;
    }
    public function claim_history_id()
    {
        return $this->hasOne(ClaimHistory::class, 'id', 'claim_history_id')->select(['id', 'code','text']);;
    }

    public function car_type_insurance_id()
    {
        return $this->hasOne(CarTypeInsurance::class, 'id', 'car_type_insurance_id');
    }
    public function uae_license_held_for_id()
    {
        return $this->hasOne(UAELicenseHeldFor::class, 'id', 'uae_license_held_for_id');
    }

    public function customer_id()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function nationality_id()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id')->select(['id', 'code','text']);;
    }

    public function payment_status_id()
    {
        return $this->hasOne(PaymentStatus::class, 'id', 'payment_status_id');
    }

    public function kyc_status_id()
    {
        return $this->hasOne(KycStatus::class, 'id', 'kyc_status_id');
    }

    public function quote_status_id()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }
    public function vehicle_detail_id()
    {
        return $this->hasOne(VehicleDetailCarQuote::class, 'car_quote_id', 'id');
    }

    public function payment_detail()
    {
        return $this->hasOne(CarQuotePayment::class, 'car_quote_id', 'id');
    }

    public function insurance_coverage()
    {
        return $this->hasOne(CarQuoteInsuranceCoverage::class, 'car_quote_id', 'id');
    }
    public function car_quote_kyc()
    {
        return $this->hasOne(CarQuoteKyc::class, 'car_quote_id', 'id');
    }
    public function pa_id()
    {
        return $this->hasOne(User::class, 'id', 'pa_id')->select(['id', 'email','name']);
    }

    public function advisor_id()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id')->select(['id', 'email','name']);
    }


    /*****  NewRelationships so old should not effect */

    public function relations() {

        if($this->isGetList)
            return [];
        else
            return ["payment_detail","quote_status_id", "kyc_status_id", "insurance_coverage.insurance_company_id", "insurance_coverage.insurance_plan_id", "insurance_coverage.vehicle_type_id", "uae_license_held_for_id", "car_make_id", "car_model_id", "emirate_of_registration_id", "claim_history_id",  "nationality_id", "vehicle_detail_id", "pa_id", "car_quote_kyc"];
    }

    public $access = [

        'write' => ['advisor'],
        'update' => ['advisor','invoicing', 'pa'],
        'delete' => ['advisor'],
        'access' => [
            "pa" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at','car_value', "pa_id","invoicing"],
            "advisor" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at','car_value' ],
            "admin" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at','car_value' ],
            "invoicing" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at','car_value','invoicing']
        ],
        "list" => [
            "pa" => [ 'id','code', 'first_name', 'last_name',  'created_at', "pa_id", "kyc_status_id","quote_status_id","aml_status","invoicing"],
            "advisor" => [ 'id','code', 'first_name', 'last_name',  'created_at', "pa_id", "kyc_status_id","quote_status_id","aml_status","invoicing"],
            "admin" => [ 'id','code', 'first_name', 'last_name',  'created_at', "kyc_status_id","quote_status_id","aml_status","invoicing"],
            "invoicing" => [ 'id','code', 'first_name', 'last_name',  'created_at' , "kyc_status_id","quote_status_id","aml_status","invoicing"]
        ],
        "detail" => [
            "pa" => [ 'id','code', 'dob','first_name', 'last_name', 'email', 'mobile_no','Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
            "advisor" => [ 'id','code','dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
            "admin" => [ 'id','code', 'dob','first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
            "invoicing" => [ 'id','code','dob', 'first_name', 'last_name', 'email', 'mobile_no','Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
        ]
    ];

    public function processGetDSL($filters, $request) {

        if($request->form_id){
            return parent::processGetBaseDSL($filters, false);
        }else{
            $restrictFilter = [];

            if(Auth::user()->hasRole('advisor')) {
                if(!array_key_exists('code', $filters)){
                    return [];
                }else{
                    $restrictFilter["code"] = $filters["code"];
                    $restrictFilter["advisor_id"] = Auth::user()->id;
                }
            }

            if(Auth::user()->hasRole('pa')) {
                if(!array_key_exists('pa_id', $filters)){
                    return [];
                }else{
                    $pa_id = $filters["pa_id"] == 0 ? NULL : Auth::user()->id;
                    $restrictFilter["pa_id"] = $pa_id;
                    $restrictFilter["advisor_id"] = ["op" => "<>", "val" => ''];
                    $restrictFilter["quote_status_id"] =  ["op" => "in", "val" => [9, 11, 12, 10, 15]];
                }
            }

            if(Auth::user()->hasRole('invoicing')) {
                if(!array_key_exists('pa_id', $filters)){
                    return [];
                }else{
                    $pa_id = $filters["pa_id"] == 0 ? NULL : Auth::user()->id;
                    $restrictFilter["invoicing"] = $pa_id;
                    $restrictFilter["advisor_id"] = ["op" => "<>", "val" => ''];
                    $restrictFilter["quote_status_id"] =  ["op" => "in", "val" => [13, 14, 15]];
                }
            }

            if(empty($restrictFilter))
                return [];
            return parent::processGetBaseDSL($restrictFilter, false);
      }
    }

    public function saveForm($request, $update = false) {

        if( Auth::user()->hasRole('pa') && $request->has('action')) {
            $request->request->add(['pa_id' => Auth::user()->id]);
            return parent::saveForm($request, true);
        }
        else if(Auth::user()->hasRole('invoicing') && $request->has('action')){
            $request->request->add(['invoicing' => Auth::user()->id]);
            return parent::saveForm($request, true);
        }
        else{
            return parent::saveForm($request, $update );
        }
    }
}
