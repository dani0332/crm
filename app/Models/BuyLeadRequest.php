<?php

namespace App\Models;

use App\Enums\BuyLeadSegment;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class BuyLeadRequest extends Model
{
    use Filterable;

    protected $appends = ['segment_label', 'status_label', 'is_expired', 'is_completed', 'can_be_expired'];
    protected $fillable = [
        'quote_type_id',
        'user_id',
        'department_id',
        'requested_count',
        'allocated_count',
        'cost_per_lead',
        'request_type',
        'expires_at',
        'status',
        'segment',
        'source',
    ];
    protected $casts = [
        'requested_count' => 'integer',
        'allocated_count' => 'integer',
        'cost_per_lead' => 'float',
        'expires_at' => 'datetime',
        'segment' => BuyLeadSegment::class,
    ];

    public function segmentLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->segment?->label(),
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_completed) {
                    return 'completed';
                }

                if ($this->is_expired) {
                    return 'expired';
                }

                return $this->status;
            },
        );
    }

    public function scopeIsSIC($query)
    {
        $query->where('segment', BuyLeadSegment::SIC);
    }

    public function scopeIsNonSIC($query)
    {
        $query->where('segment', BuyLeadSegment::NON_SIC);
    }

    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class);
    }

    public function logs()
    {
        return $this->hasMany(BuyLeadRequestLog::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeNotExpired($q)
    {
        $q->where('expires_at', '>=', now())->orWhereNull('expires_at');
    }

    public function scopeActive($q)
    {
        $q->notExpired()->whereStatus('active');
    }

    public function scopeUnfulfilled($q)
    {
        $q->whereColumn('requested_count', '>', 'allocated_count');
    }

    /**
     * Scope to filter requests by their computed "completed" status.
     * A request is completed if:
     * 1. status = 'completed', OR
     * 2. allocated_count >= requested_count (regardless of raw status)
     */
    public function scopeComputedCompleted($q)
    {
        $q->where(function ($q) {
            $q->where('status', 'completed')
                ->orWhereColumn('allocated_count', '>=', 'requested_count');
        });
    }

    /**
     * Scope to filter requests by their computed "expired" status.
     * A request is expired if:
     * 1. status = 'expired' AND not computed-completed, OR
     * 2. expires_at is in the past AND not completed
     */
    public function scopeComputedExpired($q)
    {
        $q->where(function ($q) {
            $q->where(function ($q) {
                // status = 'expired' but exclude computed-completed records
                $q->where('status', 'expired')
                    ->whereColumn('allocated_count', '<', 'requested_count')
                    ->where('status', '!=', 'completed');
            })->orWhere(function ($q) {
                // expires_at in the past and not completed
                $q->whereNotNull('expires_at')
                    ->where('expires_at', '<', now())
                    ->whereColumn('allocated_count', '<', 'requested_count')
                    ->where('status', '!=', 'completed');
            });
        });
    }

    /**
     * Scope to filter requests by their computed "active" or "processing" status.
     * Only includes requests that are NOT completed and NOT expired.
     */
    public function scopeComputedActiveStatus($q, string $status)
    {
        $q->where('status', $status)
            ->whereColumn('allocated_count', '<', 'requested_count') // Not completed by allocation
            ->where(function ($q) {
                // Not expired by date
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeIsValue($q)
    {
        $q->where('request_type', 'value');
    }

    public function scopeIsVolume($q)
    {
        $q->where('request_type', 'volume');
    }

    public function scopeByValueOrVolume($q, QuoteTypes $quoteType, bool $isValue)
    {
        $q->when($isValue, function ($q) use ($quoteType) {
            $q->whereHas('user', function ($q) use ($quoteType) {
                $q->isValueUser($quoteType);
            })->isValue();
        }, function ($q) use ($quoteType) {
            $q->whereHas('user', function ($q) use ($quoteType) {
                $q->isVolumeUser($quoteType);
            })->isVolume();
        });
    }

    public function scopeBySegment($q, bool $isSIC)
    {
        $q->when($isSIC, function ($q) {
            $q->isSIC();
        }, function ($q) {
            $q->isNonSIC();
        });
    }

    public function scopeCatA($query)
    {
        $query->where('source', LeadSourceEnum::REVIVAL);
    }

    public function scopeNonCatA($query)
    {
        $query->whereNull('source');
    }

    public function isExpired(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_completed) {
                    return false;
                }

                if ($this->status === 'expired') {
                    return true;
                }

                if (! empty($this->expires_at) && $this->expires_at < now()) {
                    return true;
                }

                return false;
            }
        );
    }

    public function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'completed' || $this->allocated_count >= $this->requested_count,
        );
    }

    public function canBeExpired(): Attribute
    {
        return Attribute::make(
            get: function () {
                return ! $this->is_completed &&
                        ! $this->is_expired &&
                        $this->status === 'active';
            }
        );
    }

    public function expire()
    {
        return $this->update([
            'status' => 'expired',
            'expires_at' => now(),
        ]);
    }

    public static function getRequestedUserIds(QuoteTypes $quoteType, bool $isSIC, bool $isValue): array
    {
        $userIds = self::byValueOrVolume($quoteType, $isValue)->nonCatA()->bySegment($isSIC)->where('quote_type_id', $quoteType->id())->active()->unfulfilled()->pluck('user_id')->toArray();

        return array_values(array_unique($userIds));
    }

    public static function getRequest(QuoteTypes $quoteType, bool $isSIC, int $userId, bool $isValue): ?BuyLeadRequest
    {
        return self::byValueOrVolume($quoteType, $isValue)
            ->nonCatA()
            ->bySegment($isSIC)
            ->where('quote_type_id', $quoteType->id())
            ->where('user_id', $userId)
            ->active()
            ->unfulfilled()
            ->first();
    }

    public function buyLead($lead, QuoteTypes $quoteType)
    {
        $this->refresh();

        if ($this->allocated_count >= $this->requested_count) {
            info("BuyLeadRequest: All leads have been allocated for this request: {$this->id} for uuid: {$lead->uuid}");
            $this->completeProcessing();

            return;
        }

        $this->increment('allocated_count');
        $this->logs()->create([
            'quote_type_id' => $quoteType->id(),
            'quote_id' => $lead->id,
            'uuid' => $lead->uuid,
        ]);

        $this->completeProcessing();
    }

    public function startProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    public function completeProcessing()
    {
        $this->refresh();

        if ($this->requested_count === $this->allocated_count) {
            $this->update(['status' => 'completed']);
        } else {
            $this->update(['status' => 'active']);
        }
    }

    public static function getCatAUserIds(bool $isSIC)
    {
        return self::catA()->active()->unfulfilled()->pluck('user_id')->toArray();
    }

    public static function getCatARequest(QuoteTypes $quoteType, bool $isSIC, int $userId): ?BuyLeadRequest
    {
        return self::catA()
            ->where('quote_type_id', $quoteType->id())
            ->where('user_id', $userId)
            ->active()
            ->unfulfilled()
            ->first();
    }
}
