<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PrivateClientConfig extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'pcp_config';
    protected $fillable = [
        'name',
        'is_active',
        'quote_type_id',
        'field_name',
        'operator',
        'value',
        'currency_type_id',
        'status',
    ];
}
