<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Auth;
use App\Jobs\FTCMailServiceJob;
use LookUpModel;

class CarQuote extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_request';
    protected $casts = [
        'dob' => 'datetime',
    ];
    protected $guarded = [];


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

    public function carQuoteRequestDetail()
    {
        return $this->hasOne(CarQuoteRequestDetail::class, 'car_quote_request_id', 'id');
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
    public function invoicing()
    {
        return $this->hasOne(User::class, 'id', 'invoicing')->select(['id', 'email','name']);
    }

    public function advisor_id()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id')->select(['id', 'email','name']);
    }

    public function oe_id()
    {
        return $this->hasOne(User::class, 'id', 'oe_id')->select(['id', 'email','name']);
    }


    /*****  NewRelationships so old should not effect */

    public function relations() {

        if($this->isGetList)
            return ["pa_id","invoicing", "quote_status_id"];
        else
            return ["payment_detail","quote_status_id", "kyc_status_id", "insurance_coverage.insurance_company_id", "insurance_coverage.insurance_plan_id", "insurance_coverage.vehicle_type_id", "uae_license_held_for_id", "car_make_id", "car_model_id", "emirate_of_registration_id", "claim_history_id",  "nationality_id", "vehicle_detail_id", "pa_id", "car_quote_kyc"];
    }

    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor','invoicing', 'pa', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            "pa" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', "pa_id","invoicing"],
            "production_approval_manager" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', "pa_id","invoicing"],
            "advisor" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at','car_value', 'dob', 'nationality_id' ],
            "oe" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at','car_value', 'dob', 'nationality_id' ],
            "admin" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at','car_value' ],
            "invoicing" => [ 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at','invoicing']
        ],
        "list" => [
            "pa" => [ 'id','code', 'first_name', 'last_name',  'created_at', "pa_id", "kyc_status_id","quote_status_id","aml_status","invoicing","car_value"],
            "production_approval_manager" => [ 'id','code', 'first_name', 'last_name',  'created_at', "pa_id", "kyc_status_id","quote_status_id","aml_status","invoicing","car_value"],
            "advisor" => [ 'id','code', 'first_name', 'last_name', 'updated_at', 'created_at', "pa_id", "kyc_status_id","quote_status_id","aml_status","invoicing","car_value"],
            "oe" => [ 'id','code', 'first_name', 'last_name', 'updated_at', 'created_at', "pa_id", "kyc_status_id","quote_status_id","aml_status","invoicing","car_value"],
            "admin" => [ 'id','code', 'first_name', 'last_name',  'created_at', "kyc_status_id","quote_status_id","aml_status","invoicing","car_value"],
            "invoicing" => [ 'id','code', 'first_name', 'last_name',"pa_id",  'created_at' , "kyc_status_id","quote_status_id","aml_status","invoicing","car_value"]
        ],
        "detail" => [
            "pa" => [ 'id','code', 'dob','first_name', 'last_name', 'email', 'mobile_no','Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id","car_value", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
            "production_approval_manager" => [ 'id','code', 'dob','first_name', 'last_name', 'email', 'mobile_no','Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id","car_value", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
            "advisor" => [ 'id','code','dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id","car_value", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
            "oe" => [ 'id','code','dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id","car_value", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
            "admin" => [ 'id','code', 'dob','first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id","car_value", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
            "invoicing" => [ 'id','code','dob', 'first_name', 'last_name', 'email', 'mobile_no','Year_of_manufacture',"kyc_status_id","quote_status_id", 'created_at', "car_make_id", "car_model_id","car_value", "emirate_of_registration_id", "claim_history_id",  "nationality_id","uae_license_held_for_id", "pa_id","aml_status","invoicing"],
        ]
    ];

    public function processGetDSL($filters, $request) {

        if($request->form_id){
            return parent::processGetBaseDSL($filters, false);
        }else{
            $restrictFilter = [];

            if(Auth::user()->hasRole('advisor')) {
                if(empty($filters)){
                    $restrictFilter["advisor_id"] = Auth::user()->id;
                }else{
                    $restrictFilter["advisor_id"] = Auth::user()->id;
                    $restrictFilter = array_merge($restrictFilter,$filters);
                }
            }

            if(Auth::user()->hasRole('oe')) {
                if(empty($filters)){
                    $restrictFilter["oe_id"] = Auth::user()->id;
                }else{
                    $restrictFilter["oe_id"] = Auth::user()->id;
                    $restrictFilter = array_merge($restrictFilter,$filters);
                }
            }

            if(Auth::user()->hasRole('pa')) {
                if(!array_key_exists('pa_id', $filters)){
                    return [];
                }else{

                    $valuesIn = [];
                    array_push($valuesIn,  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'ftc_accepted']));
                    array_push($valuesIn,  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'kyc_cleared']));
                    array_push($valuesIn,  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'missing_documents_requested']));
                    array_push($valuesIn,  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'ftc_resubmitted']));
                    array_push($valuesIn,  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_approved']));

                    $pa_id = $filters["pa_id"] == 0 ? NULL : Auth::user()->id;
                    $restrictFilter["pa_id"] = $pa_id;
                    $restrictFilter["advisor_id"] = ["op" => "<>", "val" => ''];
                    $restrictFilter["quote_status_id"] =  ["op" => "in", "val" => $valuesIn];
                }   
            }

            if(Auth::user()->hasRole('invoicing')) {
                if(!array_key_exists('pa_id', $filters)){
                    return [];
                }else{

                    $valuesIn = [];
                    array_push($valuesIn,  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'AMLScreeningCleared']));
                    array_push($valuesIn,  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_declined']));
                    array_push($valuesIn,  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_approved']));


                    $pa_id = $filters["pa_id"] == 0 ? NULL : Auth::user()->id;
                    $restrictFilter["invoicing"] = $pa_id;
                    $restrictFilter["advisor_id"] = ["op" => "<>", "val" => ''];
                    $restrictFilter["quote_status_id"] =  ["op" => "in", "val" => $valuesIn];
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

            $carQuote = CarQuote::where(['id' => $request->form_id])->whereNull('pa_id')->first();
            if($carQuote) {
                $templateParams = [
                    'notes' => "Your approval request has been assigned to a Production team member",
                    "first_name" => $carQuote->first_name,
                    "last_name" => $carQuote->last_name,
                    "code" => $carQuote->code
                ];

                $advisorEmail = $carQuote->advisor_id()->get()->first()->email;
                if($advisorEmail) {

                    $params = [
                        'to' => $advisorEmail,
                        'subject' => LookUpModel::subjectForFTCEmailCarQuote($carQuote),
                        'templateName' => 'notification',
                        'templateParams' => $templateParams
                    ];
                    
                    $oeId = $carQuote->oe_id()->first();
                    if($oeId && $oeId->email)
                        $params['cc'] = $oeId->email;

                    dispatch(new FTCMailServiceJob($params));
                }
            }
            return  parent::saveForm($request, true);
        }
        else if(Auth::user()->hasRole('invoicing') && $request->has('action')){
            $request->request->add(['invoicing' => Auth::user()->id]);

            $carQuote = CarQuote::where(['id' => $request->form_id])->whereNull('invoicing')->first();
            if($carQuote) {
                $templateParams = [
                    'notes' => "Lead has been assigned to a Payment team member",
                    "first_name" => $carQuote->first_name,
                    "last_name" => $carQuote->last_name,
                    "code" => $carQuote->code
                ];

                $advisorEmail = $carQuote->advisor_id()->get()->first()->email;
                if($advisorEmail) {
                    $params = [
                        'to' => $advisorEmail,
                        'subject' => LookUpModel::subjectForFTCEmailCarQuote($carQuote),
                        'templateName' => 'notification',
                        'templateParams' => $templateParams
                    ];
                    
                    $oeId = $carQuote->oe_id()->first();
                    if($oeId && $oeId->email)
                        $params['cc'] = $oeId->email;
                    dispatch(new FTCMailServiceJob($params));
                }
            }
            
            return parent::saveForm($request, true);
        }
        else{
            return parent::saveForm($request, $update );
        }
    }
}
