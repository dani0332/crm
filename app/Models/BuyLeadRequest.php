<?php

namespace App\Models;

use App\Enums\QuoteTypes;
use Illuminate\Database\Eloquent\Model;

class BuyLeadRequest extends Model
{
    protected $fillable = [
        'quote_type_id',
        'user_id',
        'requested_count',
        'allocated_count',
        'cost_per_lead',
        'request_type',
        'expires_at',
        'status',
    ];
    protected $casts = [
        'requested_count' => 'integer',
        'allocated_count' => 'integer',
        'cost_per_lead' => 'float',
        'expires_at' => 'datetime',
    ];

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

    public function scopeValueVolume($q, bool $isValue)
    {
        $q->when($isValue, function ($q) {
            $q->whereHas('user', function ($q) {
                $q->isValueUser();
            })->isValue();
        }, function ($q) {
            $q->whereHas('user', function ($q) {
                $q->isVolumeUser();
            })->isVolume();
        });
    }

    public static function getRequestedUserIds(QuoteTypes $quoteType, bool $isValue): array
    {
        $userIds = self::valueVolume($isValue)->where('quote_type_id', $quoteType->id())->active()->unfulfilled()->pluck('user_id')->toArray();

        return array_values(array_unique($userIds));
    }

    public static function getRequest(QuoteTypes $quoteType, int $userId, bool $isValue): ?BuyLeadRequest
    {
        return self::valueVolume($isValue)->where('quote_type_id', $quoteType->id())->where('user_id', $userId)->active()->unfulfilled()->first();
    }

    public function buyLead($lead, QuoteTypes $quoteType)
    {
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
