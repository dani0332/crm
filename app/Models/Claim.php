<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Claim extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, SoftDeletes;

    protected $table = 'claims';
    protected $fillable = [
        // New FRD fields
        'ref_id',
        'line_of_business_id',
        'claim_type_id',
        'claim_sub_status_id',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
        'health_claim_service_type',
        'health_service_type',
        'approved_repair_amount',
        'approved_total_loss_amount',
        'approved_cash_loss_amount',
        'claim_denial_reason',
        'incident_date',
        'lead_source',
        'policy_advisor',
        'insurer_claim_number',
        'assigned_claims_manager_id',
        'claims_manager_id',
        'claims_manager_assigned_date',
        'claim_status',
        'next_follow_up_date',
        'complaint_status',
        'updated_by_id',

        // Existing legacy fields
        'first_name',
        'last_name',
        'email_address',
        'phone_number',
        'insurance_company',
        'policy_number',
        'additional_notes',
        'ticket_number',
        'assigned_to_id',
        'created_by_id',
        'modified_by_id',
        'type_of_insurances_id',
        'sub_type_of_insurance_id',
        'claims_status_id',
        'car_repair_coverage_id',
        'car_repair_type_id',
        'rent_a_car_id',
        'car_make_id',
        'car_model_id',
        'plate_number',
        'insurer_reference',
        'standard_excess_payable',
        'liability',
        'workshop',
        'date_of_loss',
        'claim_amount',
        'is_deleted',
        'rent_a_car',
        'is_rent_a_car',
        'insurance_provider_id',
    ];
    protected $casts = [
        'claims_manager_assigned_date' => 'datetime',
        'next_follow_up_date' => 'datetime',
        'incident_date' => 'date',
        'date_of_loss' => 'date',
        'approved_repair_amount' => 'decimal:2',
        'approved_total_loss_amount' => 'decimal:2',
        'approved_cash_loss_amount' => 'decimal:2',
        'claim_amount' => 'decimal:2',
        'standard_excess_payable' => 'decimal:2',
        'liability' => 'decimal:2',
        'is_deleted' => 'boolean',
        'is_rent_a_car' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Filterable fields for search functionality
    public $filterables = [
        'ref_id' => FilterTypes::EXACT,
        'first_name' => FilterTypes::EXACT,
        'last_name' => FilterTypes::EXACT,
        'email_address' => FilterTypes::EXACT,
        'phone_number' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
        'claim_status' => FilterTypes::IN,
        'claim_sub_status_id' => FilterTypes::IN,
        'assigned_claims_manager_id' => FilterTypes::IN,
        'claims_manager_id' => FilterTypes::IN,
        'claims_manager_assigned_date' => FilterTypes::DATE_BETWEEN,
        'line_of_business_id' => FilterTypes::IN,
        'plate_number' => FilterTypes::EXACT,
        'vehicle_make' => FilterTypes::EXACT,
        'vehicle_model' => FilterTypes::EXACT,
        'vehicle_year' => FilterTypes::EXACT,
        'health_claim_service_type' => FilterTypes::IN,
        'health_service_type' => FilterTypes::IN,
        'incident_date' => FilterTypes::DATE_BETWEEN,
        'lead_source' => FilterTypes::IN,
        'policy_advisor' => FilterTypes::EXACT,
        'policy_number' => FilterTypes::EXACT,
        'next_follow_up_date' => FilterTypes::DATE_BETWEEN,
        'complaint_status' => FilterTypes::IN,
        // Legacy fields
        'type_of_insurances_id' => FilterTypes::IN,
        'sub_type_of_insurance_id' => FilterTypes::IN,
        'claims_status_id' => FilterTypes::IN,
        'assigned_to_id' => FilterTypes::IN,
        'date_of_loss' => FilterTypes::DATE_BETWEEN,
        'insurance_company' => FilterTypes::EXACT,
        'ticket_number' => FilterTypes::EXACT,
        'insurer_reference' => FilterTypes::EXACT,
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($claim) {
            if (Auth::check()) {
                $claim->created_by_id = Auth::id();
                $claim->updated_by_id = Auth::id();
            }

            // Generate ref_id if not provided
            if (empty($claim->ref_id)) {
                $claim->ref_id = self::generateRefId();
            }

            // Set default values for FRD fields
            if (empty($claim->complaint_status)) {
                $claim->complaint_status = 'N/A';
            }

            if (empty($claim->claim_status)) {
                $claim->claim_status = 'Open';
            }

            if (empty($claim->lead_source)) {
                $claim->lead_source = 'IMCRM';
            }
        });

        static::updating(function ($claim) {
            if (Auth::check()) {
                $claim->updated_by_id = Auth::id();
            }
        });

        static::deleting(function ($claim) {
            $claim->documents()->delete();
            $claim->claimsAttachments()->delete();
        });
    }

    /**
     * Generate a unique reference ID for the claim
     * Format: CLM-<8 digit unique characters>
     */
    private static function generateRefId(): string
    {
        $prefix = 'CLM-';

        // Generate 8 random alphanumeric characters
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        $refId = $prefix.$randomString;

        // Ensure uniqueness
        while (self::where('ref_id', $refId)->exists()) {
            $randomString = '';
            for ($i = 0; $i < 8; $i++) {
                $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
            }
            $refId = $prefix.$randomString;
        }

        return $refId;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activities::class, 'claim_id');
    }

    /**
     * FRD Relationships
     */
    public function lineOfBusiness(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class, 'line_of_business_id');
    }

    public function claimType(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'claim_type_id');
    }

    public function claimStatus(): BelongsTo
    {
        return $this->belongsTo(ClaimStatus::class, 'claims_status_id');
    }

    public function claimSubStatus(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'claim_sub_status_id');
    }

    /**
     * Legacy Relationships (for backward compatibility)
     */
    public function typeOfInsurance(): BelongsTo
    {
        return $this->belongsTo(TypeOfInsurance::class, 'type_of_insurances_id');
    }

    public function subTypeOfInsurance(): BelongsTo
    {
        return $this->belongsTo(SubTypeOfInsurance::class, 'sub_type_of_insurance_id');
    }

    public function carMake(): BelongsTo
    {
        return $this->belongsTo(CarMake::class, 'car_make_id');
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }

    public function claimsStatus(): BelongsTo
    {
        return $this->belongsTo(ClaimStatus::class, 'claims_status_id');
    }

    public function carRepairCoverage(): BelongsTo
    {
        return $this->belongsTo(CarRepairCoverage::class, 'car_repair_coverage_id');
    }

    public function carRepairType(): BelongsTo
    {
        return $this->belongsTo(CarRepairType::class, 'car_repair_type_id');
    }

    public function rentACar(): BelongsTo
    {
        return $this->belongsTo(RentACar::class, 'rent_a_car_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by_id');
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id');
    }

    public function claimsAttachments(): HasMany
    {
        return $this->hasMany(ClaimsAttachments::class, 'claim_id');
    }

    /**
     * Scopes for FRD requirements
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('assigned_claims_manager_id', $userId)
                ->orWhere('claims_manager_id', $userId)
                ->orWhere('assigned_to_id', $userId); // Legacy field
        });
    }

    public function scopeUnassigned($query)
    {
        return $query->where('assigned_claims_manager_id', null)
            ->where('claims_manager_id', null)
            ->where('assigned_to_id', null);
    }

    public function scopeByLineOfBusiness($query, $lob)
    {
        return $query->where(function ($q) use ($lob) {
            $q->where('line_of_business_id', $lob)
                ->orWhere('type_of_insurances_id', $lob); // Legacy field
        });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where(function ($q) use ($status) {
            $q->where('claims_status_id', $status);
        });
    }

    public function scopeByComplaintStatus($query, $status)
    {
        return $query->where('complaint_status', $status);
    }

    public function scopeWithNextFollowUp($query, $date = null)
    {
        $date = $date ?: now()->toDateString();

        return $query->where('next_follow_up_date', '<=', $date);
    }

    public function scopeByHealthServiceType($query, $type)
    {
        return $query->where('health_claim_service_type', $type);
    }

    /**
     * Accessors for FRD requirements
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getEmailAttribute(): string
    {
        return $this->email_address ?? '';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->next_follow_up_date && $this->next_follow_up_date->isPast();
    }

    /**
     * Mutators
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email_address'] = $value;
    }

    /**
     * FRD Business Logic Methods
     */
    public function updateClaimSubStatus($subStatusId, $reason = null)
    {
        $this->claim_sub_status_id = $subStatusId;

        // Auto-update main claim status based on sub-status rules
        $this->updateClaimStatusBasedOnSubStatus($subStatusId);

        if ($reason) {
            $this->claim_denial_reason = $reason;
        }

        $this->save();
    }

    private function updateClaimStatusBasedOnSubStatus($subStatusId)
    {
        // Get the sub-status lookup
        $subStatus = Lookup::find($subStatusId);
        if (! $subStatus) {
            return;
        }

        // Define closed statuses based on FRD requirements
        $closedStatuses = [
            'Repair completed and claim settled',
            'Total loss paid and claim settled',
            'Cash loss paid and claim settled',
            'Claim withdrawn',
            'Claim denied',
            'Claim Paid',
            'Request Denied',
            'Request Approved',
            'Answered & Closed',
        ];

        if (in_array($subStatus->name, $closedStatuses)) {
            $this->claim_status = 'Closed';
        } else {
            $this->claim_status = 'Open';
        }
    }

    public function assignClaimsManager($managerId)
    {
        $this->claims_manager_id = $managerId;
        $this->claims_manager_assigned_date = now();
        $this->save();
    }

    public function setNextFollowUp($date, $notes = null)
    {
        $this->next_follow_up_date = $date;
        if ($notes) {
            $this->additional_notes = $notes;
        }
        $this->save();
    }

    public function updateComplaintStatus($status, $notes = null)
    {
        $this->complaint_status = $status;

        // Auto-update main claim status based on complaint status
        if ($status === 'Complaint Open') {
            $this->claim_status = 'Open';
        }

        if ($notes) {
            $this->additional_notes = $notes;
        }

        $this->save();
    }

    public function assignedClaimsManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_claims_manager_id');
    }

    public function claimsManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claims_manager_id');
    }
}
