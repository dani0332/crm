<?php

namespace App\Jobs;

use App\Enums\ProcessStatusCode;
use App\Models\RenewalsBatchEmails;
use App\Services\HomeRenewalService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ScheduleHomeRenewalOcbEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch = null;
    protected $renewalsBatchEmailId = null;
    public $tries = 3;
    public $timeout = 80;
    public $backoff = 360;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $batch, int $renewalsBatchEmailId)
    {
        $this->batch = $batch;
        $this->renewalsBatchEmailId = $renewalsBatchEmailId;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(HomeRenewalService $homeRenewalService)
    {
        LoggerService::info('CL: ScheduleHomeRenewalOcbEmails OCB email schedule is started', extra: [
            'batch' => $this->batch,
        ]);

        $homeRenewalService->scheduleHomeOCB($this->batch, $this->renewalsBatchEmailId);

        LoggerService::info('CL: ScheduleHomeRenewalOcbEmails OCB email schedule is completed', extra: [
            'batch' => $this->batch,
        ]);
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalsBatchEmailId))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::error('CL: '.get_class().' FN: failed. Job Failed', extra: [
            'batch' => $this->batch,
            'exception' => $exception->getMessage(),
        ]);
        RenewalsBatchEmails::where('id', $this->renewalsBatchEmailId)->update(['status' => ProcessStatusCode::FAILED]);
    }
}
