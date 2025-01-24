<?php

namespace App\Models;

use App\Enums\CourierSyncStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmbeddedTransaction extends Model
{
    use HasFactory;

    protected $guarded = [];

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
                    'is_failed' => $this->isSyncFailed(),
                    'is_pending' => $this->isSyncPending(),
                    'message' => $this->isSyncInProgress() ? 'Sync In Progress' : (
                        $this->courier_sync_message ?? 'This transaction is pending for sync'
                    ),
                ];
            }
        );
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
}
