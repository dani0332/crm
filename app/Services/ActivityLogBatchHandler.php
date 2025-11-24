<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityLogBatchHandler
{
    private const CACHE_KEY = 'activity_log_batch';
    private const CACHE_BATCH_UUID_KEY = 'activity_log_batch_uuid';
    private const CACHE_TTL = 300; 

    protected static ?int $batchSize = null;

    public function __construct()
    {
        if (self::$batchSize === null) {
            self::$batchSize = config('activitylog.batch_size', 10);
        }
    }

    /**
     * Add activity to batch queue
     */
    public function addToBatch(ActivityLog $activity): void
    {
        // Convert activity to array for batching
        $activityData = $this->formatActivity($activity);

        // Get existing batch from cache
        $batch = Cache::get(self::CACHE_KEY, []);
        
        // Add new activity to batch
        $batch[] = $activityData;
        
        // Store back in cache
        Cache::put(self::CACHE_KEY, $batch, self::CACHE_TTL);

        $currentBatchSize = count($batch);        
        // Only insert when batch is full
        if ($currentBatchSize >= self::$batchSize) {
            LoggerService::info("Batch size reached ({$currentBatchSize}), inserting batch...");
            self::insertBatch();
        }
    }

    /**
     * Get or create batch UUID
     * Used to group activities into a single batch for insertion into the database 
     * This is used to ensure that all activities are inserted into the database in a single transaction
     */
    private function getOrCreateBatchUuid(): string
    {
        $batchUuid = Cache::get(self::CACHE_BATCH_UUID_KEY);
        
        if (!$batchUuid) {
            $batchUuid = (string) Str::uuid();
            Cache::put(self::CACHE_BATCH_UUID_KEY, $batchUuid, self::CACHE_TTL);
        }

        return $batchUuid;
    }

    /**
     * Format activity model to array for batch insertion
     */
    protected function formatActivity(ActivityLog $activity): array
    {
        $batchUuid = $this->getOrCreateBatchUuid();

        $activityData = $activity->getAttributes();

        // Ensure properties is JSON encoded if it's an array
        if (isset($activityData['properties'])) {
            if (is_array($activityData['properties'])) {
                $activityData['properties'] = json_encode($activityData['properties']);
            }
        } else {
            $activityData['properties'] = null;
        }

        // Add timestamps if not set
        $now = now();
        $activityData['created_at'] = $now;
        $activityData['updated_at'] = $now;
        $activityData['batch_uuid'] = $batchUuid;

        return $activityData;
    }

    /**
     * Insert batch to database
     */
    public static function insertBatch(): void
    {
        // Get batch from cache
        $batch = Cache::get(self::CACHE_KEY, []);
        
        if (empty($batch)) {
            return;
        }

        try {
            // Get batch UUID
            $batchUuid = Cache::get(self::CACHE_BATCH_UUID_KEY);
            
            // Ensure all activities have batch_uuid
            if ($batchUuid) {
                foreach ($batch as &$activity) {
                    $activity['batch_uuid'] = $batchUuid;
                }
                unset($activity);
            }

            // Bulk insert using query builder to bypass model save
            $instance = new ActivityLog();
            $instance->getConnection()->table($instance->getTable())->insert($batch);

            $batchCount = count($batch);
        
            // Clear the batch from cache
            self::reset();

            // Log success
            LoggerService::info("Activity log batch inserted: {$batchCount} records");
        } catch (\Exception $e) {
            // Log error but don't throw to prevent breaking the application
            Log::info('Error inserting activity log batch: ' . $e->getMessage(), [
                'exception' => $e,
                'batch_count' => count($batch),
            ]);
        }
    }

    /**
     * Reset batch state (useful for testing)
     */
    public static function reset(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_BATCH_UUID_KEY);
    }
}