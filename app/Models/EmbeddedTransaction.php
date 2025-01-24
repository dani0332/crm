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
                    'message' => $this->courier_sync_message ?? 'This transaction is pending for sync',
                ];
            }
        );
    }

    public function getSyncStatus()
    {
        if ($this->courier_sync_failed_at) {
            return 'Failed';
        } elseif ($this->courier_synced_at) {
            return 'Synced';
        }

        return 'Pending';
    }

    public function isSyncFailed()
    {
        return ! empty($this->courier_sync_failed_at);
    }

    public function isSyncPending()
    {
        return empty($this->courier_synced_at) && empty($this->courier_sync_failed_at);
    }

    public function scopeFilterBySyncStatus($query, CourierSyncStatusEnum $status)
    {
        if ($status === CourierSyncStatusEnum::SYNCED) {
            return $query->whereNotNull('courier_synced_at');
        }

        if ($status === CourierSyncStatusEnum::FAILED) {
            return $query->whereNotNull('courier_sync_failed_at');
        }

        return $query->whereNull('courier_synced_at')->whereNull('courier_sync_failed_at');
    }
}
