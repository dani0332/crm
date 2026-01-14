<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity
{
    /**
     * The database connection name for the model.
     * Always use the writable 'mysql' connection, even when default connection is set to 'mysql_read'.
     *
     * @var string|null
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'event',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
        'url',
        'code',
        'feature',
        'ip_address',
        'user_agent',
    ];
}
