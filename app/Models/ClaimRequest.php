<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria; 
use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany; 
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Class ClaimRequest
 *
 * Represents a claim request in the new claim management system
 *
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property string|null $incident
 * @property \Carbon\Carbon|null $incident_date
 * @property string|null $claim_decline_reason
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $mobile_no
 * @property int|null $customer_id
 * @property string|null $source
 * @property int|null $manager_id
 * @property \Carbon\Carbon|null $manager_assigned_date
 * @property string|null $quote_uuid
 * @property int|null $quote_type_id
 * @property int|null $personal_quote_id
 * @property int|null $insurance_provider_id
 * @property string|null $policy_number
 * @property string|null $claim_number
 * @property int|null $claim_status_id
 * @property int|null $claim_sub_status_id
 * @property int|null $claim_type_id
 * @property int|null $claim_request_type_id
 * @property bool $whatsapp_consent
 * @property float|null $approved_repair_amount
 * @property float|null $approved_total_loss_amount
 * @property float|null $approved_cash_loss_amount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ClaimRequest extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory;

    protected $table = 'claim_requests';
    protected $fillable = [
        'incident',
        'claim_decline_reason',
        'incident_date', 
        'first_name',
        'last_name',
        'email',
        'mobile_no',
        'customer_id',
        'source',
        'manager_id',
        'manager_assigned_date',
        'quote_uuid',
        'quote_type_id',
        'personal_quote_id',
        'insurance_provider_id',
        'policy_number',
        'claim_number',
        'claim_status_id',
        'claim_sub_status_id',
        'claim_type_id',
        'claim_request_type_id',
        'whatsapp_consent',
        'approved_repair_amount',
        'approved_total_loss_amount',
        'approved_cash_loss_amount',
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'incident_date' => 'datetime',
        'manager_assigned_date' => 'datetime',
        'whatsapp_consent' => 'boolean',
        'approved_repair_amount' => 'decimal:2',
        'approved_total_loss_amount' => 'decimal:2',
        'approved_cash_loss_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Filterable fields for search functionality
    public $filterables = [
        'code' => FilterTypes::EXACT,
        'first_name' => FilterTypes::EXACT,
        'last_name' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'mobile_no' => FilterTypes::EXACT,
        'policy_number' => FilterTypes::EXACT,
        'incident' => FilterTypes::EXACT,
        'source' => FilterTypes::IN,
        'created_at' => FilterTypes::DATE_BETWEEN,
        'manager_assigned_date' => FilterTypes::DATE_BETWEEN,
        'claim_status_id' => FilterTypes::IN,
        'claim_sub_status_id' => FilterTypes::IN,
        'claim_type_id' => FilterTypes::IN,
        'claim_request_type_id' => FilterTypes::IN,
        'quote_type_id' => FilterTypes::IN,
        'manager_id' => FilterTypes::IN,
        'customer_id' => FilterTypes::IN,
        'insurance_provider_id' => FilterTypes::IN,
        'whatsapp_consent' => FilterTypes::EXACT,
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($claimRequest) {
            // Set default source if not provided
            if (empty($claimRequest->source)) {
                $claimRequest->source = 'IMCRM';
            }

            // Set default whatsapp_consent if not provided
            if (is_null($claimRequest->whatsapp_consent)) {
                $claimRequest->whatsapp_consent = false;
            }
        });
 
    }
 

    /**
     * Relationships
     */
    public function claimRequestDetails()
    {
        return $this->hasOne(ClaimRequestDetail::class, 'claim_request_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id');
    }

    public function personalQuote(): BelongsTo
    {
        return $this->belongsTo(PersonalQuote::class, 'personal_quote_id');
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id');
    }

    public function claimStatus(): BelongsTo
    {
        return $this->belongsTo(ClaimsStatus::class, 'claim_status_id');
    }

    public function claimSubStatus(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'claim_sub_status_id');
    }

    public function claimType(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'claim_type_id');
    }

    public function claimRequestType(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'claim_request_type_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activities::class, 'claim_request_id');
    }

    /**
     * Scopes
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('manager_id', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('manager_id');
    }

    public function scopeByQuoteType($query, $quoteTypeId)
    {
        return $query->where('quote_type_id', $quoteTypeId);
    }

    public function scopeByStatus($query, $statusId)
    {
        return $query->where('claim_status_id', $statusId);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeWithWhatsAppConsent($query)
    {
        return $query->where('whatsapp_consent', true);
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getIsAssignedAttribute(): bool
    {
        return ! is_null($this->manager_id);
    }

    /**
     * Business Logic Methods
     */
    public function assignManager(int $managerId): void
    {
        $this->manager_id = $managerId;
        $this->manager_assigned_date = now();
        $this->save();
    }

    public function updateStatus(int $statusId, ?int $subStatusId = null): void
    {
        $this->claim_status_id = $statusId;

        if ($subStatusId) {
            $this->claim_sub_status_id = $subStatusId;
        }

        $this->save();
    }

    public function setWhatsAppConsent(bool $consent): void
    {
        $this->whatsapp_consent = $consent;
        $this->save();
    }

    public function getDisplayCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getDisplayUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
