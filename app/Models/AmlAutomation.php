<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AmlAutomation extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'aml_automation';
    protected $fillable = [
        'code',
        'status',
        'result',
    ];
}
