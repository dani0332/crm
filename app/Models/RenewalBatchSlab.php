<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RenewalBatchSlab extends Pivot implements AuditableContract
{
    use Auditable, HasFactory;

    public $incrementing = true;

    public function team()
    {
        return $this
            ->belongsTo(
                Team::class,
                'team_id',
                'id',
                'team'
            );
    }
}
