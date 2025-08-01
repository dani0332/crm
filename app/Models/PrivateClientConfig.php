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
        'active_version',
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

    public function scopeActiveVersion($query)
    {
        $query->where('active_version', true);
    }

    public function scopeByQuoteTypeId($query, int $quoteTypeId)
    {
        $query->where('quote_type_id', $quoteTypeId);
    }

    public static function getCurrentVersion(int $quoteTypeId)
    {
        return self::byQuoteTypeId($quoteTypeId)->activeVersion()->first();
    }

    public static function getLatestVersion(int $quoteTypeId)
    {
        return self::byQuoteTypeId($quoteTypeId)->orderBy('version', 'desc')->first();
    }

    public static function findByVersion(int $quoteTypeId, int $version)
    {
        return self::byQuoteTypeId($quoteTypeId)->whereVersion($version)->first();
    }
}
