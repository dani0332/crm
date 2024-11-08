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
        'value_cost_per_lead',
        'volume_cost_per_lead',
        'expires_at',
    ];
    protected $casts = [
        'requested_count' => 'integer',
        'allocated_count' => 'integer',
        'value_cost_per_lead' => 'float',
        'volume_cost_per_lead' => 'float',
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

    public function scopeActive($q)
    {
        $q->where('expires_at', '>=', now());
    }

    public function scopeUnfulfilled($q)
    {
        $q->whereColumn('requested_count', '>', 'allocated_count');
    }

    public static function getRequestedUserIds(QuoteTypes $quoteType): array
    {
        return self::where('quote_type_id', $quoteType->id())->active()->unfulfilled()->pluck('user_id')->toArray();
    }

    public static function getRequest(QuoteTypes $quoteType, int $userId): ?BuyLeadRequest
    {
        return self::where('user_id', $userId)->where('quote_type_id', $quoteType->id())->active()->unfulfilled()->first();
    }

    public function buyLead($lead, QuoteTypes $quoteType, $cost)
    {
        $this->increment('allocated_count');
        $this->logs()->create([
            'quote_type_id' => $quoteType->id(),
            'quote_id' => $lead->id,
            'uuid' => $lead->uuid,
            'cost_per_lead' => $cost,
        ]);
    }
}
