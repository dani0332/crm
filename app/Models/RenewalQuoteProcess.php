<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalQuoteProcess extends Model
{
    use HasFactory;

    protected $fillable = ['renewals_upload_lead_id', 'quote_id', 'quote_type', 'policy_number', 'data', 'batch', 'validation_errors', 'status', 'email_sent', 'type', 'fetch_plans_status', 'renewal_batch_id', 'step', 'retry_count', 'last_step_attempted'];
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function carQuote()
    {
        return $this->belongsTo(CarQuote::class, 'quote_id');
    }

    public function homeQuote()
    {
        return $this->belongsTo(HomeQuote::class, 'quote_id');
    }

    public function personalQuote()
    {
        return $this->belongsTo(PersonalQuote::class, 'quote_id');
    }

    public function renewalBatch()
    {
        return $this->belongsTo(RenewalBatch::class, 'renewal_batch_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function healthQuote()
    {
        return $this->belongsTo(HealthQuote::class, 'quote_id');
    }

    /**
     * json encode data.
     * todo: fix later as its not preserving order
     *
     * @return void
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode(json_encode($value));
    }

    /**
     * @return mixed
     */
    public function getDataAttribute($data)
    {
        return json_decode(json_decode($data, true), true);
    }

    /**
     * json encode validation_errors.
     *
     * @return void
     */
    public function setValidationErrorsAttribute($value)
    {
        $this->attributes['validation_errors'] = json_encode($value);
    }

    /**
     * Prepare data for bulk insert (bypasses mutators).
     * Applies the same encoding logic as mutators for consistency.
     *
     * @param  array  $attributes
     * @return array
     */
    public static function prepareForBulkInsert(array $attributes): array
    {
        // Double json_encode for 'data' to match setDataAttribute mutator
        if (isset($attributes['data']) && is_array($attributes['data'])) {
            $attributes['data'] = json_encode(json_encode($attributes['data']));
        }

        // Single json_encode for 'validation_errors' to match setValidationErrorsAttribute mutator
        if (isset($attributes['validation_errors']) && is_array($attributes['validation_errors'])) {
            $attributes['validation_errors'] = json_encode($attributes['validation_errors']);
        }

        return $attributes;
    }

}
