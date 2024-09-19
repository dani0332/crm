<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
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
