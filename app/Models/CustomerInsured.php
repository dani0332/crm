<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CustomerInsured extends Model
{
    protected $table = 'customer_insured';
    protected $guarded = [];

    /**
     * Cast attributes to native types
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to filter active records only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by quote
     */
    public function scopeForQuote($query, int $quoteTypeId, int $quoteRequestId)
    {
        return $query->where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
        ]);
    }

    /**
     * Create or update an active customer-insured record and deactivate previous ones
     *
     * Uses row-level locking to prevent race conditions where concurrent requests
     * could create multiple active records for the same quote.
     *
     * @param  array  $conditions  Must include quote_type_id and quote_request_id
     * @param  array  $attributes  Additional attributes to set
     * @param  bool  $skipTransaction  Skip transaction wrapper if already within a transaction
     */
    public static function createOrUpdateActive(array $conditions, array $attributes = [], bool $skipTransaction = false): self
    {
        // Validate required fields
        if (! isset($conditions['quote_type_id']) || ! isset($conditions['quote_request_id'])) {
            throw new \InvalidArgumentException('quote_type_id and quote_request_id are required');
        }

        $operation = function () use ($conditions, $attributes) {
            // Deactivate all existing records for this quote with a single update query
            // This is faster and holds locks for shorter duration than iterating
            static::forQuote(
                $conditions['quote_type_id'],
                $conditions['quote_request_id']
            )->lockForUpdate()->update(['is_active' => false]);

            // Create or update the active record
            return static::updateOrCreate($conditions, array_merge($attributes, [
                'is_active' => true,
                'updated_at' => now(),
            ]));
        };

        return $skipTransaction ? $operation() : DB::transaction($operation);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function insured()
    {
        return $this->belongsTo(Insured::class);
    }
}
