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
        'field_name',
        'operator',
        'value',
        'currency_type_id',
        'status',
    ];

    /**
     * Get the auditable data for the model.
     *
     * @return array
     */
    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }

    /**
     * Format created_at attribute
     *
     * @param  mixed  $date
     * @return string
     */
    public function getCreatedAtAttribute($date)
    {
        return $this->asDateTime($date)->format(config('constants.DATETIME_DISPLAY_FORMAT', 'Y-m-d H:i:s'));
    }

    /**
     * Format updated_at attribute
     *
     * @param  mixed  $date
     * @return string
     */
    public function getUpdatedAtAttribute($date)
    {
        return $this->asDateTime($date)->format(config('constants.DATETIME_DISPLAY_FORMAT', 'Y-m-d H:i:s'));
    }
}
