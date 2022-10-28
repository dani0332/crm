<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Rule extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'rules';

    protected $fillable = ['name', 'rule_start_date', 'rule_end_date', 'is_active'];
}
