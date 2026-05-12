<?php

namespace App\Models;

use App\Traits\UsesTestConnection;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity
{
    use UsesTestConnection;

    /**
     * The database connection name for the model.
     * Always use the writable 'mysql' connection, even when default connection is set to 'mysql_read'.
     * In testing environment, automatically uses the default connection (SQLite) via UsesTestConnection trait.
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
        'attribute_changes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'collection',
        'attribute_changes' => 'collection',
    ];
}
