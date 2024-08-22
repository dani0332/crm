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


    public function sicConfigables()
    {
        return $this->morphMany(SICConfigable::class, 'configable');
    }

    public function healthPlanTypes()
    {
        return $this->hasManyThrough(HealthPlanType::class, SICConfigable::class, 'sic_config_id', 'id', 'id', 'configurable_id');
    }
    public function nationalities()
    {
        return $this->hasManyThrough(Nationality::class, SICConfigable::class, 'sic_config_id', 'id', 'id', 'configurable_id');

    }

    public function memberCategories()
    {
        return $this->hasManyThrough(MemberCategory::class, SICConfigable::class, 'sic_config_id', 'id', 'id', 'configurable_id');
    }
}
