<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ActivityLogCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete activity log records older than specified days from config/activitylog.php';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get days from config file (fallback already handled in config)
        $days = (int) config('activitylog.delete_records_older_than_days');

        // Calculate the cutoff date
        $cutoffDate = Carbon::now()->subDays($days);

        // Count records to be deleted
        $recordsToDelete = ActivityLog::where('created_at', '<', $cutoffDate)->count();

        if ($recordsToDelete === 0) {
            $this->info('No activity log records found older than '.$days.' days.');
            LoggerService::info('Activity Log Cleanup Command executed: No records to delete.', extra: [
                'days' => $days,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ]);

            return Command::SUCCESS;
        }

        // Delete records older than the cutoff date
        $deletedCount = ActivityLog::where('created_at', '<', $cutoffDate)->delete();

        $this->info('Successfully deleted '.$deletedCount.' activity log records older than '.$days.' days.');

        LoggerService::info('Activity Log Cleanup Command executed successfully.', extra: [
            'deleted_count' => $deletedCount,
            'days' => $days,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);

        return Command::SUCCESS;
    }
}
