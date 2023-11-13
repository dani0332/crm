<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Enums\QuoteTypeId;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LookUpModel;
use OwenIt\Auditing\Auditable;

class CarQuote extends BaseModel
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

    protected $table = 'car_quote_request';
    protected $casts = [
        'dob' => 'datetime',
    ];
    public $filterables = [
        'first_name' => FilterTypes::FREE,
        'last_name' => FilterTypes::FREE,
        'previous_quote_policy_number' => FilterTypes::EXACT,
        'code' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'renewal_batch' => FilterTypes::EXACT,
        'quote_batch_id' => FilterTypes::IN,
    ];
    protected $guarded = [];

    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function fullName()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function uaeLicenseHeldFor()
    {
        return $this->belongsTo(UAELicenseHeldFor::class, 'uae_license_held_for_id');
    }

    public function carMake()
    {
        return $this->belongsTo(CarMake::class, 'car_make_id')->select(['id', 'code', 'text']);
    }

    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id')->select(['id', 'code', 'text']);
    }

    public function emirate()
    {
        return $this->belongsTo(Emirate::class, 'emirate_of_registration_id');
    }

    public function claimHistory()
    {
        return $this->belongsTo(ClaimHistory::class, 'claim_history_id');
    }

    public function carTypeInsurance()
    {
        return $this->belongsTo(CarTypeInsurance::class, 'car_type_insurance_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id')->select(['id', 'code', 'text']);
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class, 'quote_status_id');
    }

    public function getCreatedAtAttribute($value)
    {
        $date_time_format = config('constants.DATETIME_DISPLAY_FORMAT');

        return Carbon::parse($value)->format($date_time_format);
    }

    public function getUpdatedAtAttribute($value)
    {
        $date_time_format = config('constants.DATETIME_DISPLAY_FORMAT');

        return Carbon::parse($value)->format($date_time_format);
    }

    /*****  NewRelationships so old should not effect */

    public function car_make_id()
    {
        return $this->hasOne(CarMake::class, 'id', 'car_make_id')->select(['id', 'code', 'text']);
    }

    public function carQuoteRequestDetail()
    {
        return $this->hasOne(CarQuoteRequestDetail::class, 'car_quote_request_id', 'id');
    }

    public function insuranceProvider()
    {
        return $this->hasOne(InsuranceProvider::class, 'text', 'currently_insured_with')->select(['id', 'text']);
    }

    public function car_model_id()
    {
        return $this->hasOne(CarModel::class, 'id', 'car_model_id')->select(['id', 'code', 'text']);
    }

    public function emirate_of_registration_id()
    {
        return $this->hasOne(Emirate::class, 'id', 'emirate_of_registration_id')->select(['id', 'code', 'text']);
    }

    public function claim_history_id()
    {
        return $this->hasOne(ClaimHistory::class, 'id', 'claim_history_id')->select(['id', 'code', 'text']);
    }

    public function car_type_insurance_id()
    {
        return $this->hasOne(CarTypeInsurance::class, 'id', 'car_type_insurance_id')->select(['id', 'text']);
    }

    public function uae_license_held_for_id()
    {
        return $this->hasOne(UAELicenseHeldFor::class, 'id', 'uae_license_held_for_id')->select(['id', 'code', 'text']);
    }

    public function customer_id()
    {
        return $this->belongsTo(Customer::class);
    }

    public function nationality_id()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id')->select(['id', 'code', 'text']);
    }

    public function payment_status_id()
    {
        return $this->hasOne(PaymentStatus::class, 'id', 'payment_status_id');
    }

    public function plan_id()
    {
        return $this->hasOne(CarPlan::class, 'id', 'plan_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function plan()
    {
        return $this->belongsTo(CarPlan::class, 'plan_id');
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

    public function vehicleType()
    {
        return $this->hasOne(VehicleType::class, 'id', 'vehicle_type_id')->select(['id', 'text']);
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
        return $this->hasOne(User::class, 'id', 'pa_id')->select(['id', 'email', 'name']);
    }

    public function payment_id()
    {
        return $this->hasOne(User::class, 'id', 'payment_id')->select(['id', 'email', 'name']);
    }

    public function advisor_id()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id')->select(['id', 'email', 'name']);
    }

    public function advisor()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id')->select(['id', 'email', 'name', 'mobile_no', 'landline_no']);
    }

    public function batch()
    {
        return $this->hasOne(QuoteBatches::class, 'id', 'quote_batch_id')->select(['id', 'name', 'start_date', 'end_date']);
    }

    public function oe_id()
    {
        return $this->hasOne(User::class, 'id', 'oe_id')->select(['id', 'email', 'name']);
    }

    public function tier()
    {
        return $this->hasOne(Tier::class, 'id', 'tier_id')->select(['id', 'name', 'min_price', 'max_price', 'cost_per_lead']);
    }

    public function quoteViewCount()
    {
        return $this->hasOne(QuoteViewCount::class, 'quote_id', 'id')->where('quote_type_id', 1);
    }

    public function updatedBy()
    {
        return $this->hasOne(User::class, 'email', 'updated_by')->select(['id', 'email', 'name']);
    }

    public function scopeRelationWhere($query, $isGetList, $filters)
    {
        if (Auth::user()->hasRole('pa') && $isGetList) {
            $query->select(['car_quote_request.id', 'code', 'first_name', 'last_name', 'car_quote_request.updated_at', 'car_quote_request.created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id']);
            $query->join('car_quote_insurance_coverage', 'car_quote_insurance_coverage.car_quote_id', '=', 'car_quote_request.id');
        }
    }

    /*****  NewRelationships so old should not effect */

    public function relations()
    {
        if ($this->isGetList) {
            return ['pa_id', 'payment_id', 'quote_status_id', 'plan_id'];
        } else {
            return ['car_type_insurance_id', 'payment_detail', 'quote_status_id', 'kyc_status_id', 'insurance_coverage.insurance_company_id', 'insurance_coverage.insurance_plan_id', 'insurance_coverage.vehicle_type_id', 'uae_license_held_for_id', 'car_make_id', 'car_model_id', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'vehicle_detail_id', 'pa_id', 'car_quote_kyc',  'plan_id', 'plan_id.provider_id'];
        }
    }

    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'payment', 'invoicing', 'pa', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'pa_id', 'payment_id'],
            'production_approval_manager' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'pa_id', 'payment_id'],
            'advisor' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'car_value', 'dob', 'nationality_id'],
            'oe' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'car_value', 'dob', 'nationality_id'],
            'admin' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'car_value'],
            'payment' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'payment_id'],
            'invoicing' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'invoicing'],
        ],
        'list' => [
            'pa' => ['id', 'code', 'first_name', 'last_name',  'created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'production_approval_manager' => ['id', 'code', 'first_name', 'last_name',  'created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'advisor' => ['id', 'code', 'first_name', 'last_name', 'updated_at', 'created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'oe' => ['id', 'code', 'first_name', 'last_name', 'updated_at', 'created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'admin' => ['id', 'code', 'first_name', 'last_name',  'created_at', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'payment' => ['id', 'code', 'first_name', 'last_name', 'pa_id',  'created_at', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'invoicing' => ['id', 'code', 'first_name', 'last_name', 'pa_id',  'created_at', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'invoicing', 'plan_id'],
        ],
        'detail' => [
            'pa' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'production_approval_manager' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'advisor' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'oe' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'admin' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'payment' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'invoicing' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
        ],
    ];

    public function processGetDSL($filters, $request)
    {
        if ($request->form_id) {
            return parent::processGetBaseDSL($filters, false);
        } else {
            $restrictFilter = [];

            if (Auth::user()->hasRole('advisor')) {
                if (empty($filters)) {
                    $restrictFilter['advisor_id'] = Auth::user()->id;
                } else {
                    $restrictFilter['advisor_id'] = Auth::user()->id;
                    $restrictFilter = array_merge($restrictFilter, $filters);
                }

                // $restrictFilter["insurance_coverage.car_quote_id"] = 12222;
            }

            if (Auth::user()->hasRole('oe')) {
                if (empty($filters)) {
                    $restrictFilter['oe_id'] = Auth::user()->id;
                } else {
                    $restrictFilter['oe_id'] = Auth::user()->id;
                    $restrictFilter = array_merge($restrictFilter, $filters);
                }
            }

            if (Auth::user()->hasRole('pa')) {
                if (! array_key_exists('pa_id', $filters)) {
                    return [];
                } else {
                    $valuesIn = [];
                    array_push($valuesIn, LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'ftc_accepted']));
                    array_push($valuesIn, LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'kyc_cleared']));
                    array_push($valuesIn, LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'missing_documents_requested']));
                    array_push($valuesIn, LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'ftc_resubmitted']));
                    array_push($valuesIn, LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_approved']));

                    $pa_id = $filters['pa_id'] == 0 ? null : Auth::user()->id;
                    $restrictFilter['pa_id'] = $pa_id;
                    $restrictFilter['advisor_id'] = ['op' => '<>', 'val' => ''];

                    $restrictFilter['quote_status_id'] = ['op' => 'in', 'val' => $valuesIn];
                }
            }

            if (Auth::user()->hasRole('payment')) {
                if (! array_key_exists('pa_id', $filters)) {
                    return [];
                } else {
                    $valuesIn = [];
                    array_push($valuesIn, LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'AMLScreeningCleared']));
                    array_push($valuesIn, LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_declined']));
                    array_push($valuesIn, LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_approved']));

                    $pa_id = $filters['pa_id'] == 0 ? null : Auth::user()->id;
                    $restrictFilter['payment_id'] = $pa_id;
                    $restrictFilter['advisor_id'] = ['op' => '<>', 'val' => ''];
                    $restrictFilter['quote_status_id'] = ['op' => 'in', 'val' => $valuesIn];
                }
            } //invoicing

            if (Auth::user()->hasRole('invoicing')) {
                if (! array_key_exists('pa_id', $filters)) {
                    return [];
                } else {
                    $valuesIn = [];
                    array_push($valuesIn, LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'policy_issued']));
                    $pa_id = $filters['pa_id'] == 0 ? null : Auth::user()->id;
                    $restrictFilter['invoicing'] = $pa_id;
                    $restrictFilter['advisor_id'] = ['op' => '<>', 'val' => ''];
                    $restrictFilter['quote_status_id'] = ['op' => 'in', 'val' => $valuesIn];
                }
            }

            if (empty($restrictFilter)) {
                return [];
            }

            return parent::processGetBaseDSL($restrictFilter, false);
        }
    }

    public function saveForm($request, $update = false)
    {
        if (Auth::user()->hasRole('pa') && $request->has('action')) {
            $request->request->add(['pa_id' => Auth::user()->id]);

            return parent::saveForm($request, true);
        } elseif (Auth::user()->hasRole('payment') && $request->has('action')) {
            $request->request->add(['payment_id' => Auth::user()->id]);

            return parent::saveForm($request, true);
        } elseif (Auth::user()->hasRole('invoicing') && $request->has('action')) {
            $request->request->add(['invoicing' => Auth::user()->id]);

            return parent::saveForm($request, true);
        } else {
            return parent::saveForm($request, $update);
        }
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carLostQuoteLogs()
    {
        return $this->hasMany(CarLostQuoteLog::class, 'car_quote_request_id');
    }

    public function carLostQuoteLog()
    {
        return $this->hasOne(CarLostQuoteLog::class, 'car_quote_request_id')->latestOfMany();
    }

    public function quoteRequestEntityMapping()
    {
        return $this->hasOne(QuoteRequestEntityMapping::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Car);
    }
}
