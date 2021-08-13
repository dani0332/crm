<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Config;

class CarQuote extends Model implements AuditableContract
{
    use HasFactory, Auditable;
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
        $date_time_format = Config::get('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function processGetDSL($filters = [], $request) {

        $select = ['car_value', 'id', 'year_of_manufacture', 'car_make_id','car_model_id'];
        switch($request->query('mode')){
            case 'policy_holder_detail':
                $select = ['first_name', 'last_name', 'mobile_no', 'email','dob', 'emirate_of_registration_id'];
                break;
            case 'vehicle_detail':
                $select = ['year_of_manufacture', 'car_model_id', 'car_make_id'];
                break;
            case 'listView':
                $select = ['id', 'code', 'first_name', 'last_name', 'email' , 'mobile_no', 'created_at'];
                break;
        }
        $response = DB::table('car_quote_request')
            ->select($select)
            ->where(function($query) use($filters) {
                foreach($filters as $key => $value) {
                    $query->where($key,$value);
                }
            })
            ->get();
        return $response;
    }
}
