<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
