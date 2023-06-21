<?php

namespace App\Jobs;

use App\Mail\LeadAllocationFailedNotification as MailLeadAllocationFailedNotification;
use App\Services\LeadAllocationService;
use App\Traits\GetUserTreeTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

// Scheduled to delete, job moved to console command
class CarLeadAllocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, GetUserTreeTrait;

    public int $tries = 2;
    public int $timeout = 55;
    public int $backoff = 20;

    /**
     * Execute the job.
     */
    public function handle(LeadAllocationService $leadAllocationService): void
    {
        try {
            info('Lead Allocation Job Started');

            if ($leadAllocationService->shouldResetUserAssignmentCountAndAvailability()) {
                $leadAllocationService->setAdvisorsToUnavailable();
            }

            $leadAllocationService->updateAllocationStatusIfNeeded();

            if (! $leadAllocationService->shouldCarAllocationProceed()) {
                info('CAR Lead Allocation Job Switch is OFF');
            } else {
                info('CAR Lead Allocation Job Switch is ON and job is about to start');
                $leadAllocationService->processCarLeads();
            }

        } catch (Throwable $exception) {
            info('**************** Lead Allocation Job is timed out now at: '.now().' **************** ');
            info('Exception: '.$exception->getMessage());
            Log::error($exception);
            Mail::sendNow(new MailLeadAllocationFailedNotification($exception));
            $this->delete();
        }
    }

    public function failed(Throwable $exception)
    {
        Log::error('Exception in lead allocation: '.$exception->getMessage());
        Mail::sendNow(new MailLeadAllocationFailedNotification($exception));
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('lead_allocation'))->dontRelease()];
    }
}
