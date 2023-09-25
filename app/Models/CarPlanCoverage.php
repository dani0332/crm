<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CarPlanCoverage extends BaseModel implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'car_plan_coverage';
    protected $guarded = ['id'];
}
