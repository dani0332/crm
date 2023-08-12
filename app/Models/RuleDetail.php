<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RuleDetail extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'rule_details';

    public function rule()
    {
        return $this->belongsTo(Rule::class);
    }

}
