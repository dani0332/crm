<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerVerificationDetail extends Model
{
    use HasFactory;

    protected $table = 'customer_verification_details';

    protected $fillable = [
        'year_of_manufacture',
        'date_of_birth',
        'nationality_id',
        'vehicle_make_id',
        'vehicle_model_id',
        'uae_license_held_for_id',
        'emirate_of_registration_id',
        'quote_type_id',
        'quotable_type',
        'quotable_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function quotable(): MorphTo
    {
        return $this->morphTo();
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function carMake(): BelongsTo
    {
        return $this->belongsTo(CarMake::class, 'vehicle_make_id');
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'vehicle_model_id');
    }

    public function emirate(): BelongsTo
    {
        return $this->belongsTo(Emirate::class, 'emirate_of_registration_id');
    }

    public function uaeLicenseHeldFor(): BelongsTo
    {
        return $this->belongsTo(UAELicenseHeldFor::class, 'uae_license_held_for_id');
    }

    public function scopeForQuotable($query, $quotableType, $quotableId)
    {
        return $query->where('quotable_type', $quotableType)
                    ->where('quotable_id', $quotableId);
    }
}
