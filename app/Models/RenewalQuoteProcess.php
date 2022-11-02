<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalQuoteProcess extends Model
{
    use HasFactory;

    protected $fillable = ['renewals_upload_lead_id', 'quote_type', 'policy_number', 'data', 'batch', 'validation_errors', 'status', 'email_sent', 'type'];
    protected $casts = [
        'data' => 'array',
        'validation_errors' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function renewalUploadLead()
    {
        return $this->belongsTo(RenewalsUploadLeads::class, 'renewals_upload_lead_id');
    }

    /**
     * json encode data.
     *
     * @param $value
     * @return void
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    /**
     * json encode validation_errors.
     *
     * @param $value
     * @return void
     */
    public function setValidationErrorsAttribute($value)
    {
        $this->attributes['validation_errors'] = json_encode($value);
    }
}
