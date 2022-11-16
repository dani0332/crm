<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalQuoteProcess extends Model
{
    use HasFactory;

    protected $fillable = ['renewals_upload_lead_id', 'quote_id', 'quote_type', 'policy_number', 'data', 'batch', 'validation_errors', 'status', 'email_sent', 'type', 'fetch_plans_status'];
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
     * todo: fix later as its not preserving order
     *
     * @param $value
     * @return void
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode(json_encode($value));
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getDataAttribute($data)
    {
        return json_decode(json_decode($data, true), true);
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
