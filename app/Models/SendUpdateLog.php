<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SendUpdateLog extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $guarded = [];

    public function details(): HasMany
    {
        return $this->hasMany(SendUpdateLogDetails::class);
    }
}
