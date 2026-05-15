<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PassportVisaDetail extends Model
{
    protected $table = 'passport_visa_details';
    protected $fillable = [
        'passport_number',
        'passport_country',
        'passport_expiry_date',
        'name',
        'visa_number',
        'visa_file_number',
        'visa_type',
        'visa_issue_date',
        'visa_expiry_date',
        'visa_issuance_authority',
        'profession',
        'sponsor',
        'quoteable_type',
        'quoteable_id',
        'customer_member_id',
    ];

    /**
     * Get the parent quoteable model (e.g. PersonalQuote).
     *
     * @return MorphTo
     */
    public function quoteable(): MorphTo
    {
        return $this->morphTo();
    }
}
