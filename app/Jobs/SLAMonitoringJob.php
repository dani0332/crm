<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Jobs\SLA\SendSLABreachedNotificationJob;
use App\Jobs\SLA\SendSLAReminderNotificationJob;
use App\Models\SLATracking;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SLAMonitoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = 60;

    private const DEFAULT_CHUNK_SIZE = 100;

    public function handle(): void
    {
        LoggerService::info('SLAMonitoringJob - Starting SLA monitoring cycle');

        $reminderCount = 0;
        $escalationCount = 0;

        $reminderCount = $this->sendReminderNotifications();
        $escalationCount = $this->escalateBreach();

        LoggerService::info('SLAMonitoringJob - Monitoring cycle completed', [
            'reminders_sent' => $reminderCount,
            'escalations_sent' => $escalationCount,
        ]);
    }

    private function sendReminderNotifications(): int
    {
        $reminderMinutes = (int) getAppStorageValueByKey(ApplicationStorageEnums::SLA_REMINDER_MINUTES) ?: 15;
        $reminderCount = 0;

        SLATracking::dueForReminder($reminderMinutes)
            ->chunk(self::DEFAULT_CHUNK_SIZE, function ($slaRecords) use (&$reminderCount) {
                foreach ($slaRecords as $slaRecord) {
                    try {
                        SendSLAReminderNotificationJob::dispatch($slaRecord);
                        $reminderCount++;
                    } catch (Exception $e) {
                        LoggerService::error('SLAMonitoringJob - Failed to send reminder', exception: $e);
                    }
                }
            });

        return $reminderCount;
    }

    private function escalateBreach(): int
    {
        $escalationCount = 0;

        SLATracking::dueForBreach()
            ->chunk(self::DEFAULT_CHUNK_SIZE, function ($slaRecords) use (&$escalationCount) {
                foreach ($slaRecords as $slaRecord) {
                    try {
                        SendSLABreachedNotificationJob::dispatch($slaRecord);
                        $escalationCount++;
                    } catch (Exception $e) {
                        LoggerService::error('SLAMonitoringJob - Failed to escalate breach', exception: $e);
                    }
                }
            });

        return $escalationCount;
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error('SLAMonitoringJob - Job failed', exception: $exception);
    }
}
