<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceProviderTransition extends Model
{
    use HasFactory;

    protected $table = 'renewal_insurance_provider_transitions';
    protected $fillable = ['source_insurance_provider_id', 'target_insurance_provider_id', 'description', 'is_active', 'created_by', 'updated_by'];
    protected $casts = [
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'description' => 'string',
    ];

    /**
     * Source provider (lead's insurer code e.g. RSA).
     */
    public function sourceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'source_insurance_provider_id');
    }

    /**
     * Target provider (provider to use for plan lookup e.g. GIG/AXA).
     */
    public function targetProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'target_insurance_provider_id');
    }
}
