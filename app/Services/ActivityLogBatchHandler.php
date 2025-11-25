<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Activity Log Batch Handler
 * 
 * Singleton class that manages batching of activity logs per HTTP request.
 * Activities are collected in memory during a request and bulk inserted at the end.
 */
class ActivityLogBatchHandler
{
    /**
     * Singleton instance
     */
    protected static ?self $instance = null;

    /**
     * Current batch UUID
     */
    protected ?string $batchUuid = null;

    /**
     * Batch of activities to be inserted
     */
    protected array $batch = [];

    /**
     * Whether batch is currently open
     */
    protected bool $isOpen = false;

    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct()
    {
        //
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Start a new batch
     */
    private function doStartBatch(): void
    {
        if ($this->isOpen) {
            // Batch already open, don't reset
            return;
        }

        $this->isOpen = true;
        $this->batchUuid = (string) Str::uuid();
        $this->batch = [];
    }

    /**
     * Check if there's an open batch
     */
    private function doIsOpen(): bool
    {
        return $this->isOpen;
    }

    /**
     * End the current batch and insert all activities
     * 
     * If batch is empty or not open, only resets the state.
     * Otherwise, bulk inserts all activities and resets.
     */
    private function doEndBatch(): void
    {
        if (!$this->isOpen || empty($this->batch)) {
            $this->doReset();
            return;
        }

        $this->insertBatch();
        $this->doReset();
    }

    /**
     * Add activity to batch queue
     * 
     * If batch is not open, saves immediately to database.
     * Otherwise, adds to in-memory batch array for bulk insert.
     */
    private function doAddToBatch(ActivityLog $activity): void
    {
        // If batch is not open, save immediately (bypass batching)
        if (!$this->isOpen) {
            $activity->getConnection()
                ->table($activity->getTable())
                ->insert($this->formatActivity($activity, includeBatchUuid: false));
            return;
        }

        // Add to in-memory batch array for bulk insert at end of request
        $this->batch[] = $this->formatActivity($activity, includeBatchUuid: true);
    }

    /**
     * Format activity model to array for batch insertion or direct insert
     * 
     * @param ActivityLog $activity The activity log model to format
     * @param bool $includeBatchUuid Whether to include batch_uuid in the formatted data
     * @return array Formatted activity data ready for database insertion
     */
    protected function formatActivity(ActivityLog $activity, bool $includeBatchUuid = true): array
    {
        $activityData = $activity->getAttributes();

        // Ensure properties is JSON encoded if it's an array
        if (isset($activityData['properties']) && is_array($activityData['properties'])) {
            $activityData['properties'] = json_encode($activityData['properties']);
        } else {
            $activityData['properties'] = $activityData['properties'] ?? null;
        }

        // Add timestamps if not set
        $now = now();
        $activityData['created_at'] = $now;
        $activityData['updated_at'] = $now;
        
        // Include batch_uuid only if requested and batch is open
        if ($includeBatchUuid && $this->isOpen && $this->batchUuid) {
            $activityData['batch_uuid'] = $this->batchUuid;
        }

        return $activityData;
    }

    /**
     * Insert batch to database using bulk insert
     */
    protected function insertBatch(): void
    {
        if (empty($this->batch)) {
            return;
        }

        try {
            // Ensure all activities have batch_uuid
            foreach ($this->batch as &$activityData) {
                if (!isset($activityData['batch_uuid'])) {
                    $activityData['batch_uuid'] = $this->batchUuid;
                }
            }
            unset($activityData);

            // Bulk insert using query builder to bypass model save
            $model = new ActivityLog();
            $model->getConnection()
                ->table($model->getTable())
                ->insert($this->batch);

            $batchCount = count($this->batch);
            LoggerService::info("Activity log batch inserted: {$batchCount} records");
        } catch (\Exception $e) {
            // Log error but don't throw to prevent breaking the application
            Log::error('Error inserting activity log batch: ' . $e->getMessage(), [
                'exception' => $e,
                'batch_count' => count($this->batch),
                'batch_uuid' => $this->batchUuid,
            ]);
        }
    }

    /**
     * Reset batch state
     * 
     * Clears batch array, UUID, and closes the batch.
     */
    private function doReset(): void
    {
        $this->batch = [];
        $this->batchUuid = null;
        $this->isOpen = false;
    }

    /**
     * Static methods for convenience (delegate to singleton instance)
     * These are the public API methods
     */
    public static function startBatch(): void
    {
        self::getInstance()->doStartBatch();
    }

    public static function isOpen(): bool
    {
        return self::getInstance()->doIsOpen();
    }

    public static function endBatch(): void
    {
        self::getInstance()->doEndBatch();
    }

    public static function addToBatch(ActivityLog $activity): void
    {
        self::getInstance()->doAddToBatch($activity);
    }
}