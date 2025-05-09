<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PrivateClientConfigHistory extends Model implements AuditableContract
{
    use Auditable, SoftDeletes;

    protected $table = 'pcp_config_history';
    protected $fillable = [
        'pcp_config_id',
        'quote_type_id',
        'field_name',
        'operator',
        'value',
        'currency_type_id',
        'status',
        'version',
    ];

    /**
     * Get the parent configuration
     */
    public function config()
    {
        return $this->belongsTo(PrivateClientConfig::class, 'pcp_config_id');
    }
}
