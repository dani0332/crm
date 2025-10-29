<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'nationality_id', 
    ];
    protected $casts = [
        'driver_dob' => 'date',
        'driver_license_issue_date' => 'date',
        'driver_license_expiry_date' => 'date',
        'uae_driving_experience' => 'integer',
        'nationality_id' => 'integer',
    ];

    public function driverLicenseIssueDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    public function driverLicenseExpiryDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    public function driverDob(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function quoteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForQuotable($query, $quotableType, $quotableId)
    {
        return $query->where('quoteable_type', $quotableType)
            ->where('quoteable_id', $quotableId);
    }
}
