<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DrivingLicenseDetail extends Model
{
    protected $table = 'driving_license_details';
    
    protected $fillable = [
        'first_name',
        'last_name',
        'dob',
        'gender',
        'license_number',
        'license_issue_place',
        'license_issue_date',
        'license_expiry_date',
        'traffic_code_number',
        'uae_driving_experience',
        'nationality_id',
    ];
    
    protected $casts = [
        'dob' => 'date',
        'license_issue_date' => 'date',
        'license_expiry_date' => 'date',
        'uae_driving_experience' => 'integer',
        'nationality_id' => 'integer',
    ];

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    /**
     * Get the parent licensable model (CarQuote).
     */
    public function licensable(): MorphTo
    {
        return $this->morphTo();
    }
}
