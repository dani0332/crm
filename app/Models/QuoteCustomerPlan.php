<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteCustomerPlan extends Model
{
    use HasFactory;

    protected $table = 'quote_customer_plans';
    protected $fillable = [
        'quote_uuid',
        'quote_type_id',
        'plan',
    ];
    protected $casts = [
        'plan' => 'array',
    ];

    /**
     * Get the personal quote that owns this customer plan.
     */
    public function personalQuote(): BelongsTo
    {
        return $this->belongsTo(PersonalQuote::class, 'quote_uuid', 'uuid');
    }

    /**
     * Scope to filter by quote UUID
     */
    public function scopeByQuoteUuid($query, string $uuid)
    {
        return $query->where('quote_uuid', $uuid);
    }

    /**
     * Scope to filter by quote type
     */
    public function scopeByQuoteType($query, int $quoteTypeId)
    {
        return $query->where('quote_type_id', $quoteTypeId);
    }

    /**
     * Get provider name from plan JSON
     */
    public function getProviderNameAttribute(): ?string
    {
        return $this->plan['providerName'] ?? null;
    }

    /**
     * Get plan name from plan JSON
     */
    public function getPlanNameAttribute(): ?string
    {
        return $this->plan['name'] ?? null;
    }
}
