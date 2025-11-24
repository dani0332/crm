<?php

namespace App\Models;

use App\Services\ActivityLogBatchHandler;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity
{
    /**
     * Static instance of batch handler (singleton pattern)
     */
    protected static ?ActivityLogBatchHandler $batchHandler = null;

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
        'feature',
        'ip_address',
        'user_agent',
        'device_type',
        'code',
    ];

    /**
     * Get or create batch handler instance
     */
    protected static function getBatchHandler(): ActivityLogBatchHandler
    {
        if (self::$batchHandler === null) {
            self::$batchHandler = new ActivityLogBatchHandler();
        }

        return self::$batchHandler;
    }

    /**
     * Override save method to batch activities
     * 
     * Spatie Activity Log uses $activity->save() internally,
     * so we override this method to intercept and batch activities.
     */
    public function save(array $options = [])
    {
        // If batching is disabled, save immediately
        if (!config('activitylog.batch_enabled', true)) {
            return parent::save($options);
        }

        // Add to batch queue instead of saving immediately
        self::getBatchHandler()->addToBatch($this);

        // Return true to indicate "saved" (even though it's queued)
        return true;
    }
}