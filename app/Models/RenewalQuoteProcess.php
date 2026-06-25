<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenewalQuoteProcess extends Model
{
    use HasFactory;

    protected $fillable = ['renewals_upload_lead_id', 'quote_id', 'quote_type', 'policy_number', 'data', 'batch', 'validation_errors', 'status', 'email_sent', 'type', 'fetch_plans_status', 'renewal_batch_id', 'insurance_provider_transition_id', 'step', 'retry_count', 'last_step_attempted', 'step_errors'];
    protected $casts = [
        'data' => 'array',
        'validation_errors' => 'array',
        'step_errors' => 'array',
        'insurance_provider_transition_id' => 'integer',
    ];

    /**
     * @return BelongsTo
     */
    public function renewalUploadLead()
    {
        return $this->belongsTo(RenewalsUploadLeads::class, 'renewals_upload_lead_id');
    }

    /**
     * @return BelongsTo
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
     * Transition used for this lead (source insurer → target provider), if any.
     *
     * @return BelongsTo
     */
    public function insuranceProviderTransition()
    {
        return $this->belongsTo(InsuranceProviderTransition::class, 'insurance_provider_transition_id');
    }

    /**
     * Whether this process is a transitionable lead (has a resolved and active provider transition).
     */
    public function checkIsTransitionableLead(): bool
    {
        if ($this->insurance_provider_transition_id === null) {
            return false;
        }

        // Ensure transition exists, is active, and both providers exist
        // to maintain consistency with RenewalsUploadService::isTransitionableLeadForProcess
        $transition = $this->insuranceProviderTransition;

        return (bool) ($transition &&
            $transition->is_active &&
            $transition->targetProvider &&
            $transition->sourceProvider);
    }

    /**
     * @return BelongsTo
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
     * Applies the same encoding logic as mutators for consistency,
     * and ensures created_at/updated_at timestamps are set.
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

        // Set timestamps if not already set (Model::insert() does not do this automatically)
        $now = now();
        if (! isset($attributes['created_at'])) {
            $attributes['created_at'] = $now;
        }
        if (! isset($attributes['updated_at'])) {
            $attributes['updated_at'] = $now;
        }

        return $attributes;
    }

}
