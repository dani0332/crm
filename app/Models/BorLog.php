<?php

namespace App\Models;

use App\Enums\BorStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class BorLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_sent' => 'boolean',
        'policy_expiry' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($borLog) {
            // Generate document_id if not set (used for customer portal access)
            // if (empty($borLog->document_id)) {
            //     $borLog->document_id = Str::random(64);
            // }
            // if (empty($borLog->date_created)) {
            //     $borLog->date_created = now();
            // }
            // Set default status to SIGNATURE_REQUESTED
            if (empty($borLog->status)) {
                $borLog->status = BorStatusEnum::SIGNATURE_REQUESTED;
            }
            // Generate bor_reference as IM-BOR-ddmmyy-count
            if (empty($borLog->bor_reference) && !empty($borLog->lead_id)) {
                $borLog->bor_reference = static::generateBorId($borLog->lead_id);
            }
        });
    }

    /**
     * @return string
     */
    public function getCreatedAtAttribute($date)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($date)->timezone(config('app.timezone'))->format($date_time_format);
    }

    /**
     * @return string
     */
    public function getUpdatedAtAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.datetime_format'));
    }

    /**
     * @return string
     */
    public function getPolicyExpiryAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.DATE_FORMAT_ONLY'));
    }

    /**
     * @return string
     */
    public function getDateSignedAttribute($table)
    {
        if (empty($table) || $table == null) {
            return null;
        }
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.DATETIME_DISPLAY_FORMAT'));
    }

    /**
     * @return string
     */
    public function getDateUploadedAttribute($table)
    {
        if (empty($table) || $table == null) {
            return null;
        }
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.DATETIME_DISPLAY_FORMAT'));
    }

    /**
     * @return string
     */
    public function getDateCreatedAttribute($table)
    {
        if (empty($table) || $table == null) {
            return null;
        }
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.DATETIME_DISPLAY_FORMAT'));
    }

    /**
     * Get the personal quote (lead) that owns the BOR log.
     */
    public function personalQuote(): BelongsTo
    {
        return $this->belongsTo(PersonalQuote::class, 'lead_id', 'id');
    }

    /**
     * Get all documents related to this BOR request.
     * Uses the polymorphic relationship from quote_documents table.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(QuoteDocument::class, 'quote_document_id');
    }

    /**
     * Get signed documents related to this BOR request.
     * Uses the polymorphic relationship from quote_documents table.
     */
    public function signedDocument(): HasOne
    {
        return $this->hasOne(QuoteDocument::class, 'document_category', 'bor_reference');
    }

    /**
     * Get the insurance contact associated with this BOR request.
     */
    public function insuranceContact(): BelongsTo
    {
        return $this->belongsTo(InsuranceProviderContact::class, 'insurance_contact_id');
    }

    /**
     * Get the specific quote document associated with this BOR log.
     * This is a direct one-to-one relationship.
     */
    public function quoteDocument(): BelongsTo
    {
        return $this->belongsTo(QuoteDocument::class, 'quote_document_id');
    }

    /**
     * Get the insurance provider associated with this BOR log.
     */
    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
    }

    /**
     * Check if the BOR request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === BorStatusEnum::COMPLETED;
    }

    /**
     * Check if the BOR request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === BorStatusEnum::CANCELLED;
    }

    /**
     * Check if the BOR request has been signed.
     */
    public function isSigned(): bool
    {
        return $this->status === BorStatusEnum::DOCUMENT_SIGNED;
    }

    /**
     * Check if documents have been uploaded.
     */
    public function isUploaded(): bool
    {
        return $this->status === BorStatusEnum::DOCUMENT_UPLOADED;
    }

    /**
     * Check if email has been sent to insurer.
     */
    public function isEmailSent(): bool
    {
        return $this->email_sent;
    }

    /**
     * Check if the status is active (not cancelled or completed).
     */
    public function isActive(): bool
    {
        return BorStatusEnum::isActive($this->status);
    }

    /**
     * Check if the status is final (cancelled or completed).
     */
    public function isFinal(): bool
    {
        return BorStatusEnum::isFinal($this->status);
    }

    /**
     * Check if the BOR can be edited.
     */
    public function allowsEditing(): bool
    {
        return BorStatusEnum::allowsEditing($this->status);
    }

    /**
     * Check if the BOR can be cancelled.
     */
    public function allowsCancellation(): bool
    {
        return BorStatusEnum::allowsCancellation($this->status);
    }

    /**
     * Check if documents can be uploaded.
     */
    public function allowsUpload(): bool
    {
        return BorStatusEnum::allowsUpload($this->status);
    }

    /**
     * Check if the BOR can be marked as done.
     */
    public function allowsMarkingDone(): bool
    {
        return BorStatusEnum::allowsMarkingDone($this->status);
    }

    /**
     * Check if documents can be viewed.
     */
    public function allowsViewDocument(): bool
    {
        return BorStatusEnum::allowsViewDocument($this->status);
    }

    /**
     * Check if the customer portal link can be copied.
     */
    public function allowsCopyLink(): bool
    {
        return BorStatusEnum::allowsCopyLink($this->status);
    }

    /**
     * Transition the BOR to "Document Signed" status.
     */
    public function markAsSigned(): bool
    {
        if ($this->isPending()) {
            $this->status = BorStatusEnum::DOCUMENT_SIGNED;
            $this->date_signed = now();
            return $this->save();
        }
        return false;
    }

    /**
     * Transition the BOR to "Document Uploaded" status.
     */
    public function markAsUploaded(): bool
    {
        if ($this->isSigned()) {
            $this->status = BorStatusEnum::DOCUMENT_UPLOADED;
            $this->date_uploaded = now();
            return $this->save();
        }
        return false;
    }

    /**
     * Transition the BOR to "Completed" status.
     */
    public function markAsCompleted(?string $additional_notes = null): bool
    {
        if ($this->isUploaded() || $this->isSigned()) {
            $this->status = BorStatusEnum::COMPLETED;
            $this->additional_notes = $additional_notes;
            return $this->save();
        }
        return false;
    }

    /**
     * Transition the BOR to "Cancelled" status.
     */
    public function markAsCancelled(?string $reason = null, ?string $additional_notes = null): bool
    {
        // if ($this->allowsCancellation()) {
            $this->status = BorStatusEnum::CANCELLED;
            if ($reason) {
                $this->cancellation_reason = $reason;
                $this->additional_notes = $additional_notes;
            }
            return $this->save();
        // }
        // return false;
    }

    /**
     * Get the next possible statuses for this BOR.
     */
    public function getNextStatuses(): array
    {
        return BorStatusEnum::getNextStatuses($this->status);
    }

    /**
     * Get status display information.
     */
    public function getStatusInfo(): array
    {
        return [
            'value' => $this->status,
            'label' => BorStatusEnum::labels()[$this->status] ?? $this->status,
            'color' => BorStatusEnum::colors()[$this->status] ?? 'gray',
            'icon' => BorStatusEnum::icons()[$this->status] ?? 'circle',
        ];
    }

    /**
     * Generate a new document ID (for customer portal access).
     */
    public function regenerateDocumentId(): string
    {
        $this->document_id = Str::random(64);
        $this->save();
        return $this->document_id;
    }

    /**
     * Generate a unique BOR ID in the format IM-BOR-ddmmyy-count
     * 
     * @param int $leadId The lead ID for which to generate the BOR ID
     * @param \DateTime|null $date Optional date, defaults to current date
     * @return string The generated unique BOR ID
     * @throws \Exception If unable to generate unique ID after retries
     */
    public static function generateBorId(int $leadId, ?\DateTime $date = null): string
    {
        $date = $date ?? now();
        $dateStr = $date->format('dmyHis');
        $maxRetries = 10;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Use database transaction to ensure thread-safety
                return DB::transaction(function () use ($leadId, $dateStr) {
                    // Get the highest count for this lead and date
                    $todayStart = now()->startOfDay();
                    $todayEnd = now()->endOfDay();
                    
                    $lastCount = static::where('lead_id', $leadId)
                        ->lockForUpdate() // Prevent concurrent access
                        ->count();
                    
                    $count = $lastCount + 1;
                    $borId = "IM-BOR-{$dateStr}-{$count}";
                    
                    // Double-check uniqueness
                    $exists = static::where('bor_reference', $borId)->exists();
                    if ($exists) {
                        throw new \Exception("BOR ID collision detected: {$borId}");
                    }
                    
                    return $borId;
                });
            } catch (\Exception $e) {
                if ($attempt === $maxRetries) {
                    Log::error('Failed to generate unique BOR ID after maximum retries', [
                        'lead_id' => $leadId,
                        'date' => $dateStr,
                        'attempts' => $maxRetries,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception("Unable to generate unique BOR ID for lead {$leadId} after {$maxRetries} attempts");
                }
                
                // Brief delay before retry to reduce collision chances
                usleep(rand(100000, 500000)); // 0.1-0.5 seconds
            }
        }
        
        // This should never be reached due to the exception thrown in the loop
        throw new \Exception("Unexpected error in BOR ID generation");
    }

    /**
     * Validate if a BOR ID follows the correct format
     * 
     * @param string $borId The BOR ID to validate
     * @return bool True if valid format, false otherwise
     */
    public static function isValidBorIdFormat(string $borId): bool
    {
        return preg_match('/^IM-BOR-\d{6}-\d+$/', $borId) === 1;
    }

    /**
     * Extract date and count from a BOR ID
     * 
     * @param string $borId The BOR ID to parse
     * @return array|null Array with 'date' and 'count' keys, or null if invalid format
     */
    public static function parseBorId(string $borId): ?array
    {
        if (!static::isValidBorIdFormat($borId)) {
            return null;
        }
        
        $parts = explode('-', $borId);
        if (count($parts) !== 4) {
            return null;
        }
        
        return [
            'date' => $parts[2], // ddmmyy format
            'count' => (int) $parts[3]
        ];
    }
}
