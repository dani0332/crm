<?php

namespace App\Jobs;

use App\Enums\ProcessStatusCode;
use App\Models\RenewalsBatchEmails;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ScheduleRenewalOcbEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $batch = null;
    protected  $renewalsBatchEmail = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batch, RenewalsBatchEmails $renewalsBatchEmail)
    {
        $this->batch = $batch;
        $this->renewalsBatchEmail = $renewalsBatchEmail;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {
        info('CL: ScheduleRenewalOcbEmails OCB email schedule is started');

        $renewalsUploadService->scheduleRenewalsOcbEmails($this->batch, $this->renewalsBatchEmail);

        info('CL: ScheduleRenewalOcbEmails OCB email schedule is completed');
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalsBatchEmail->id))->dontRelease()];
    }

    /**
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
        $this->renewalsBatchEmail->update(['status' => ProcessStatusCode::FAILED]);
    }
}
