<?php

namespace App\Models;

use App\Enums\BorStatusEnum;
use App\Enums\DocumentTypeCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($borLog) {
            // Set default status to SIGNATURE_REQUESTED
            if (empty($borLog->status)) {
                $borLog->status = BorStatusEnum::SIGNATURE_REQUESTED;
            }
            // Generate bor_reference as IM-BOR-ddmmyy-count
            if (empty($borLog->bor_reference) && ! empty($borLog->personal_quote_id)) {
                $borLog->bor_reference = static::generateBorId($borLog->personal_quote_id);
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
        if (empty($table) || $table == null) {
            return null;
        }

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
        return $this->belongsTo(PersonalQuote::class, 'personal_quote_id', 'id');
    }

    /**
     * Get all documents related to this BOR request.
     * Uses the polymorphic relationship from quote_documents table.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(QuoteDocument::class, 'quote_document_id')->whereIn('document_type_code', [
            DocumentTypeCode::BAL,
            DocumentTypeCode::BAL_BS,
            DocumentTypeCode::BAL_BIKE,
            DocumentTypeCode::BAL_TRVL,
            DocumentTypeCode::BAL_HOME,
            DocumentTypeCode::BAL_HLTH,
            DocumentTypeCode::BAL_PET,
            DocumentTypeCode::BAL_YCHT,
            DocumentTypeCode::BAL_CYCLE,
            DocumentTypeCode::BAL_LIFE,
        ]);
    }

    /**
     * Get signed documents related to this BOR request.
     * Uses the polymorphic relationship from quote_documents table.
     */
    public function signedDocument(): BelongsTo
    {
        return $this->belongsTo(QuoteDocument::class, 'quote_document_id')->where('document_type_code', DocumentTypeCode::BOR_SIGN);
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
        $this->status = BorStatusEnum::CANCELLED;
        if ($reason) {
            $this->cancellation_reason = $reason;
            $this->additional_notes = $additional_notes;
        }

        return $this->save();
    }

    /**
     * Generate a unique BOR ID in the format IM-BOR-ddmmyy-count
     *
     * @param  int  $leadId  The lead ID for which to generate the BOR ID
     * @param  \DateTime|null  $date  Optional date, defaults to current date
     * @return string The generated unique BOR ID
     *
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

                    $lastCount = static::where('personal_quote_id', $leadId)
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
                        'personal_quote_id' => $leadId,
                        'date' => $dateStr,
                        'attempts' => $maxRetries,
                        'error' => $e->getMessage(),
                    ]);
                    throw new \Exception("Unable to generate unique BOR ID for lead {$leadId} after {$maxRetries} attempts");
                }

                // Brief delay before retry to reduce collision chances
                usleep(rand(100000, 500000)); // 0.1-0.5 seconds
            }
        }

        // This should never be reached due to the exception thrown in the loop
        throw new \Exception('Unexpected error in BOR ID generation');
    }
}
