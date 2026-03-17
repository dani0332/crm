<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Class CustomerBankAccount
 *
 * Represents a customer's bank account information associated with a claim request
 */
class CustomerBankAccount extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory;

    protected $table = 'customer_bank_accounts';
    protected $fillable = [
        'customer_id',
        'type',
        'claim_uuid',
        'bank_name',
        'account_name',
        'iban',
        'swift_code',
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Filterable fields for search functionality
    public $filterables = [
        'customer_id' => FilterTypes::IN,
        'claim_uuid' => FilterTypes::EXACT,
        'type' => FilterTypes::IN,
        'bank_name' => FilterTypes::EXACT,
        'account_name' => FilterTypes::EXACT,
        'iban' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
    ];

    /**
     * Relationships
     */

    /**
     * Get the customer that owns the bank account
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the claim request associated with this bank account
     */
    public function claimRequest(): BelongsTo
    {
        return $this->belongsTo(ClaimRequest::class, 'claim_uuid', 'uuid');
    }

    /**
     * Scope a query to only include bank accounts of a specific type
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
