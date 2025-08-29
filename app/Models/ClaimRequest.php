<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use App\Traits\QuoteTraits\QuoteAllocatable;

/**
 * Class ClaimRequest
 *
 * Represents a claim request in the new claim management system
 */
class ClaimRequest extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory , QuoteAllocatable;

    protected $table = 'claim_requests';
    
    
}
