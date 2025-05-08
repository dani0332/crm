<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NationalityAllocationConfiguration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quote_type_id',
        'nationality_id',
        'created_by',
        'updated_by',
    ];

    /**
     * The users that belong to the nationality allocation configuration.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Get the nationality that owns the configuration.
     */
    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class, );
    }

    /**
     * Get the quote type that owns the configuration.
     */
    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class, );
    }

    /**
     * Get the user who created this configuration.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this configuration.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
