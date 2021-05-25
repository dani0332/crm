<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarQoute extends Model
{
    use HasFactory;
    protected $table = 'car_quote_request';
    protected $casts = [
        'dob' => 'datetime',
    ];


    public function uaeLicenseHeldFor(){
        return $this->hasOne( UAELicenseHeldFor::class , 'id','uae_license_held_for_id');
    }

    public function carMake(){
        return $this->hasOne( CarMake::class , 'id','car_make_id');
    }

    public function carModel(){
        return $this->hasOne( CarModel::class , 'id','car_model_id');
    }


    public function emirate(){
        return $this->hasOne( Emirate::class , 'id','emirate_of_registration_id');
    }
    public function claimHistory(){
        return $this->hasOne( ClaimHistory::class , 'id','claim_history_id');
    }

    public function carTypeInsurance(){
        return $this->hasOne( CarTypeInsurance::class , 'id','car_type_insurance_id');
    }


    public function customer(){
        return $this->hasOne( Customer::class , 'id','customer_id');
    }

    public function nationality(){
        return $this->hasOne( Nationality::class , 'id','nationality_id');
    }


    public function paymentStatus(){
        return $this->hasOne(PaymentStatus::class , 'id','payment_status_id');
    }


    public function quoteStatus(){
        return $this->hasOne(QuoteStatus::class , 'id','quote_status_id');
    }
}
