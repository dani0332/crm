<?php

namespace App\Models;

use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SICConfig extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

    protected $table = 'sic_configs';
    protected $guarded = [];

    public function sicConfigurables()
    {
        return $this->hasMany(SICConfigurables::class, 'sic_config_id', 'id');
    }
    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }
}
