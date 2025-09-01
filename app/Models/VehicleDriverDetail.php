<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VehicleDriverDetail extends Model
{
    protected $table = 'vehicle_driver_details';
    protected $fillable = [
        'rta_transaction_type',
        'vehicle_plate_code',
        'vehicle_plate_number',
        'traffic_code_number',
        'vehicle_engine_number',
        'rta_plate_category',
        'vehicle_color',
        'vehicle_plate_color',
        'bank_loan',
        'bank_name',
        'first_registration_date',
        'annual_mileage_estimate',
        'is_insured_and_driver_same',
        'driver_first_name',
        'driver_last_name',
        'driver_dob',
        'driver_gender',
        'driver_license_number',
        'driver_license_issue_place',
        'driver_license_issue_date',
        'driver_license_expiry_date',
        'driver_uae_driving_experience',
        'driver_home_country_license_issuance',
        'driver_home_country_driving_experience',

    ];

    public function quoteable(): MorphTo
    {
        return $this->morphTo();
    }
}
