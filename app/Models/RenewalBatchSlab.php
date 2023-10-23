<?php

namespace App\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RenewalBatchSlab extends Pivot implements AuditableContract
{
    use Auditable, HasFactory;

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
