<?php

namespace App\Models;

use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SICHealthConfig extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

    protected $table = 'sic_configs';
    protected $guarded = [];


    public function planTypes()
    {
        return $this->morphToMany(HealthPlanType::class, 'configurable', 'sic_configables', 'sic_config_id', 'configurable_id');
    }
    public function nationalities()
    {
        return $this->morphToMany(Nationality::class, 'configable');
    }

    public function memberCategories()
    {
        return $this->morphToMany(MemberCategory::class, 'configable');
    }
}
