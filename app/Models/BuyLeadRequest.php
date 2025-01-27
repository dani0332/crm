<?php

namespace App\Models;

use App\Enums\BuyLeadSegment;
use App\Enums\QuoteTypes;
use Illuminate\Database\Eloquent\Model;

class BuyLeadRequest extends Model
{
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
    ];
    protected $casts = [
        'requested_count' => 'integer',
        'allocated_count' => 'integer',
        'cost_per_lead' => 'float',
        'expires_at' => 'datetime',
        'segment' => BuyLeadSegment::class,
    ];

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

    public function scopeNotExpired($q)
    {
        $q->where('expires_at', '>=', now());
    }

    public function scopeActive($q)
    {
        $q->notExpired()->whereStatus('active');
    }

    public function scopeUnfulfilled($q)
    {
        $q->whereColumn('requested_count', '>', 'allocated_count');
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

    public static function getRequestedUserIds(QuoteTypes $quoteType, bool $isSIC, bool $isValue): array
    {
        $userIds = self::byValueOrVolume($quoteType, $isValue)->bySegment($isSIC)->where('quote_type_id', $quoteType->id())->active()->unfulfilled()->pluck('user_id')->toArray();

        return array_values(array_unique($userIds));
    }

    public static function getRequest(QuoteTypes $quoteType, bool $isSIC, int $userId, bool $isValue): ?BuyLeadRequest
    {
        return self::byValueOrVolume($quoteType, $isValue)->bySegment($isSIC)->where('quote_type_id', $quoteType->id())->where('user_id', $userId)->active()->unfulfilled()->first();
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
}
