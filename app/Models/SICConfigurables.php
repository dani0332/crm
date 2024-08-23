<?php

namespace App\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SICConfigurables extends Model implements AuditableContract
{
    use Auditable, HasFactory;
    protected $table = 'sic_configurables';

    public function configurable()
    {
        return $this->morphTo();
    }

    public function getConfigurableModelNameAttribute()
    {
        return get_class($this->configurable);
    }


}
