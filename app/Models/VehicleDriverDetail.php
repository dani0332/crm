<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Nationality;

class VehicleDriverDetail extends Model
{
    protected $table = 'vehicle_driver_details';

    protected $fillable = [
        'driver_first_name',
        'driver_last_name',
        'driver_dob',
        'driver_gender',
        'driver_license_number',
        'driver_license_issue_place',
        'driver_license_issue_date',
        'driver_license_expiry_date',
        'traffic_code_number',
        'vehicle_plate_code',
        'vehicle_plate_number',
        'vehicle_engine_number',
        'rta_plate_category',
        'vehicle_color',
        'bank_name',
        'first_registration_date',
    ];
    protected $casts = [
        'driver_dob' => 'date',
        'driver_license_issue_date' => 'date',
        'driver_license_expiry_date' => 'date',
        'uae_driving_experience' => 'integer',
        'nationality_id' => 'integer',
    ];

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function quoteable(): MorphTo
    {
        return $this->morphTo();
    }
}
