<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class NationalityAllocationConfiguration extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = [
        'quote_type_id',
        'nationality_id',
        'created_by',
        'updated_by',
        'should_skip_sic',
        'activated_at',
    ];
    protected $casts = [
        'should_skip_sic' => 'boolean',
        'activated_at' => 'datetime',
    ];
    protected $auditInclude = [
        'quote_type_id',
        'nationality_id',
        'should_skip_sic',
        'activated_at',
    ];

    public function generateTags(): array
    {
        return ['nationality-allocation', 'config'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('activated_at');
    }

    public function scopeInactive($query)
    {
        return $query->whereNull('activated_at');
    }

    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->activated_at !== null,
        );
    }
}
