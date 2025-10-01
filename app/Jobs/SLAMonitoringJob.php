<?php

declare(strict_types=1);

namespace App\Jobs;

use Exception;
use Throwable;
use App\Models\SLATracking;
use App\Services\SLAService;
use Illuminate\Bus\Queueable;
use App\Enums\ApplicationStorageEnums;
use App\Services\Logger\LoggerService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SLAMonitoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = 60;

    private const DEFAULT_CHUNK_SIZE = 100;

    public function handle(SLAService $slaService): void
    {
        LoggerService::info('SLAMonitoringJob - Starting SLA monitoring cycle');

        $reminderCount = 0;
        $escalationCount = 0;

        $reminderCount = $this->sendReminderNotifications($slaService);
        $escalationCount = $this->escalateBreach($slaService);

        LoggerService::info('SLAMonitoringJob - Monitoring cycle completed', [
            'reminders_sent' => $reminderCount,
            'escalations_sent' => $escalationCount,
        ]);
    }

    private function sendReminderNotifications(SLAService $slaService): int
    {
        $reminderMinutes = (int) getAppStorageValueByKey(ApplicationStorageEnums::SLA_REMINDER_MINUTES) ?: 15;
        $reminderCount = 0;

        SLATracking::dueForReminder($reminderMinutes)
            ->chunk(self::DEFAULT_CHUNK_SIZE, function ($slaRecords) use ($slaService, &$reminderCount) {
                foreach ($slaRecords as $slaRecord) {
                    try {
                        $slaService->sendReminderNotification($slaRecord);
                        $reminderCount++;
                    } catch (Exception $e) {
                        LoggerService::error('SLAMonitoringJob - Failed to send reminder', exception: $e);
                    }
                }
            });

        return $reminderCount;
    }

    private function escalateBreach(SLAService $slaService): int
    {
        $escalationCount = 0;

        SLATracking::dueForBreach()
            ->chunk(self::DEFAULT_CHUNK_SIZE, function ($slaRecords) use ($slaService, &$escalationCount) {
                foreach ($slaRecords as $slaRecord) {
                    try {
                        $slaService->escalateBreach($slaRecord);
                        $escalationCount++;
                    } catch (Exception $e) {
                        LoggerService::error('SLAMonitoringJob - Failed to escalate breach', exception: $e);
                    }
                }
            });

        return $escalationCount;
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('sla-monitoring'))->dontRelease()];
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error('SLAMonitoringJob - Job failed', exception: $exception);
    }
}
