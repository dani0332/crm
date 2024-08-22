<?php

namespace App\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SICConfigable extends Model implements AuditableContract
{
    use Auditable, HasFactory;
    protected $table = 'sic_configables';

    public function configurable()
    {
        return $this->morphTo();
    }

    public function getConfigurableModelNameAttribute()
    {
        return get_class($this->configurable);
    }


}
