<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FilterTypes;
use App\Enums\QuoteTypeId;
use App\Traits\FilterCriteria;
use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Class ClaimRequest
 *
 * Represents a claim request in the new claim management system
 */
class ClaimRequest extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory;

    protected $table = 'claim_requests';
   
   
}