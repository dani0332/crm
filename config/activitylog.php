<?php

use App\Enums\ActivityLogEventEnum;
use App\Models\ActivityLog;

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => env('ACTIVITY_LOG_CLEANUP_DAYS', 30),

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => 'default',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the current Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => false,

    /*
     * This model will be used to log activity.
     * It should implement the Spatie\Activitylog\Contracts\Activity interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'activity_model' => ActivityLog::class,

    /*
     * This is the name of the table that will be created by the migration and
     * used by the Activity model shipped with this package.
     */
    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log'),

    /*
     * Enable batching of activity logs.
     * When enabled, activities will be batched per HTTP request and inserted in bulk at the end of each request.
     */
    'batch_enabled' => env('ACTIVITY_LOGGER_BATCH_ENABLED', true),

    /*
     * Paths that should be excluded from HTTP request logging.
     */
    'excluded_paths' => [
        '/health',
        '/horizon',
        '/activity-log',
        '/activity-logs',
        '/admin/activity',
        '/google/callback',
    ],

    /*
     * Sensitive field keys that should be redacted in request payloads.
     */
    'sensitive_keys' => [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'api_key',
        'secret',
        'access_token',
        'refresh_token',
        'authorization',
    ],

    /*
     * Default event name for HTTP request logging.
     */
    'default_event' => ActivityLogEventEnum::Accessed->value,
];
