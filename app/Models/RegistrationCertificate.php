<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RegistrationCertificate extends Model
{
    protected $table = 'registration_certificates';

    protected $fillable = [
        'place_of_issue',
        'expiry_date',
        'owner',
        'nationality_id',
        'mortgage_by',
        'notes',
        'insured_with',
        'insurance_type',
        'model',
        'vehicle_class',
        'vehicle_type',
        'origin',
        'number_of_passengers',
        'gross_vehicle_weight',
        'empty_weight',
        'ocr_done_by',
        'doc_type',
        'provider_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'number_of_passengers' => 'integer',
        'nationality_id' => 'integer',
    ];

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    /**
     * Get the parent certificatable model (CarQuote).
     */
    public function certificatable(): MorphTo
    {
        return $this->morphTo();
    }
}
