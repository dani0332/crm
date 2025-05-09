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
        'version',
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

    /**
     * Get all versions of this configuration
     */
    public function versions()
    {
        return $this->hasMany(PrivateClientConfigHistory::class, 'pcp_config_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest version of this configuration
     */
    public function latestVersion()
    {
        return $this->hasOne(PrivateClientConfigHistory::class, 'pcp_config_id')
            ->latest();
    }

    /**
     * Create a new version of this config
     *
     * @return PrivateClientConfigHistory
     */
    public function createVersion(?int $userId = null)
    {
        return $this->versions()->create([
            'quote_type_id' => $this->quote_type_id,
            'field_name' => $this->field_name,
            'operator' => $this->operator,
            'value' => $this->value,
            'currency_type_id' => $this->currency_type_id,
            'status' => $this->status,
            'created_by' => $userId,
        ]);
    }
}
