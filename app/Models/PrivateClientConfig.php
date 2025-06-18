<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PrivateClientConfig extends Model implements AuditableContract
{
    use Auditable, SoftDeletes;

    protected $table = 'pcp_config';
    protected $fillable = [
        'quote_type_id',
        'quote_type',
        'config',
        'version',
        'status',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }
}
