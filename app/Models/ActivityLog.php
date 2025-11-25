<?php

namespace App\Models;

use App\Services\ActivityLogBatchHandler;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity
{

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
        'code'
    ];

    /**
     * Override save method to batch activities
     * 
     * Spatie Activity Log uses $activity->save() internally,
     * so we override this method to intercept and batch activities.
     */
    public function save(array $options = []): bool
    {
        // If batching is disabled, save immediately
        if (!config('activitylog.batch_enabled', true)) {
            return parent::save($options);
        }

        // If batch is open, add to batch queue instead of saving immediately
        if (ActivityLogBatchHandler::isOpen()) {
            ActivityLogBatchHandler::addToBatch($this);
            // Return true to indicate "saved" (even though it's queued)
            return true;
        }

        // Batch is not open, save immediately
        return parent::save($options);
    }
}