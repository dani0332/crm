<?php

namespace App\Models;

use App\Traits\FilterCriteria;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use App\Traits\QuoteModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SICHealthConfig extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;
    protected $table = "sic_health_configs";
    protected $guarded = [];
    protected $casts = [
        'plan_types' => 'json',
        'member_categories' => 'json',
        'nationalities'=> 'json',
    ];

}

