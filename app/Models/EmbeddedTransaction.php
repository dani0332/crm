<?php

namespace App\Models;

use App\Enums\CourierSyncStatusEnum;
use App\Enums\RolesEnum;
use App\Enums\SageEmbeddedProductEnum;
use App\Traits\SpatieActivityLog;
use App\Traits\UsesTestConnection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmbeddedTransaction extends Model
{
    use HasFactory, SpatieActivityLog, UsesTestConnection;

    protected $guarded = [];
    protected $appends = [
        'sage_status', // for sukoon medx
    ];

    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id', 'id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(EmbeddedProductOption::class, 'product_id', 'id');
    }
    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    /**
     * Get payment charges through payment splits
     */
    public function paymentCharges()
    {
        return $this->hasManyThrough(
            PaymentCharge::class,
            PaymentSplits::class,
            'code', // Foreign key on payment_splits table
            'payment_split_id', // Foreign key on payment_charges table
            'code', // Local key on embedded_transactions table
            'id' // Local key on payment_splits table
        );
    }

    public function travelAnnualPayments()
    {
        return $this->hasOne(Payment::class, 'code', 'code');
    }

    public function quoteRequest()
    {
        return $this->morphTo();
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function travelQuote()
    {
        return $this->belongsTo(TravelQuote::class, 'code', 'code');
    }

    public function courierSyncStatusInfo(): Attribute
    {
        return Attribute::make(
            get: function () {
                return [
                    'status' => $this->getSyncStatus(),
                    'is_syncable' => $this->isSyncable(),
                    'is_failed' => $this->isSyncFailed(),
                    'is_pending' => $this->isSyncPending(),
                    'message' => $this->isSyncInProgress() ? 'Sync In Progress' : (
                        $this->courier_sync_message ?? 'This transaction is pending for sync'
                    ),
                ];
            }
        );
    }

    public function isSyncable()
    {
        return $this->isSyncFailed() || $this->isSyncPending() || $this->isSynced() || auth()->user()->hasRole(RolesEnum::Engineering);
    }

    public function getSyncStatus()
    {
        $statusEnum = CourierSyncStatusEnum::PENDING;

        if ($this->courier_sync_started_at) {
            $statusEnum = CourierSyncStatusEnum::IN_PROGRESS;
        } elseif ($this->courier_sync_failed_at) {
            $statusEnum = CourierSyncStatusEnum::FAILED;
        } elseif ($this->courier_synced_at) {
            $statusEnum = CourierSyncStatusEnum::SYNCED;
        }

        return $statusEnum->label();
    }

    public function isSyncFailed()
    {
        return ! empty($this->courier_sync_failed_at) && empty($this->courier_sync_started_at);
    }

    public function isSyncPending()
    {
        return empty($this->courier_synced_at) && empty($this->courier_sync_failed_at);
    }

    public function isSynced()
    {
        return ! empty($this->courier_synced_at) && empty($this->courier_sync_failed_at) && empty($this->courier_sync_started_at);
    }

    public function isSyncInProgress()
    {
        return ! empty($this->courier_sync_started_at);
    }

    public function scopeFilterBySyncStatus($query, CourierSyncStatusEnum $status)
    {
        if ($status === CourierSyncStatusEnum::ALL) {
            return $query;
        }

        if ($status === CourierSyncStatusEnum::SYNCED) {
            $query->whereNotNull('courier_synced_at');
        } elseif ($status === CourierSyncStatusEnum::FAILED) {
            $query->whereNotNull('courier_sync_failed_at');
        } elseif ($status === CourierSyncStatusEnum::IN_PROGRESS) {
            $query->whereNotNull('courier_sync_started_at');
        } else {
            $query->whereNull('courier_synced_at')->whereNull('courier_sync_failed_at');
        }
    }

    public function scopeSyncFailed($q)
    {
        $q->where('courier_sync_failed_at', '<=', now()->subHour());

    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'paymentable');
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }

    public function getSageStatusAttribute()
    {
        return $this->sage_status_id ? SageEmbeddedProductEnum::getStatusById($this->sage_status_id) : null;
    }

    public function scopeIsActive($query, bool $isActive)
    {
        return $query->where('is_active', $isActive);
    }

    public function scopeEpShortCode($query, string|array $epShortCode)
    {
        return $query
            ->when(is_string($epShortCode),
                fn ($q) => $q->whereHas('product.embeddedProduct',
                    fn ($q) => $q->where('short_code', $epShortCode)
                )
            )
            ->when(is_array($epShortCode),
                fn ($q) => $q->whereHas('product.embeddedProduct',
                    fn ($q) => $q->whereIn('short_code', $epShortCode)
                )
            );
    }

    /**
     * Scope by quote request status. Applies to Car, Home, Bike, Travel, Cyber (EmbeddedProductRepository::ALLOWED_LOBS).
     * Morphs: CarQuote→car_quote_request, TravelQuote→travel_quote_request, PersonalQuote→personal_quotes (Home/Bike/Cyber). All have quote_status_id.
     */
    public function scopeQuoteRequestStatusId($query, int $quoteStatusId)
    {
        return $query->whereHasMorph(
            'quoteRequest',
            [CarQuote::class, TravelQuote::class, PersonalQuote::class],
            fn ($q) => $q->where('quote_status_id', $quoteStatusId)
        );
    }
}
