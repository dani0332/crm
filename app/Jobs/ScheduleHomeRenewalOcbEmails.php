<?php

namespace App\Jobs;

use App\Enums\ProcessStatusCode;
use App\Models\RenewalsBatchEmails;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Throwable;
use App\Services\LoggerService;



class ScheduleHomeRenewalOcbEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable;

    protected $batch = null;
    protected $renewalsBatchEmail = null;
    public $tries = 3;
    public $timeout = 80;
    public $backoff = 360;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batch)
    {
        $this->batch = $batch;
        $this->renewalsBatchEmail = $renewalsBatchEmail;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {

        // get pending leads
        $totalLeads = $this->renewalsUploadService->getPendingOcbLeadsTotalNonMotor($batch, QuoteTypeShortCode::HOM);
        
        // If there are not leads, return false
        if ($totalLeads == 0) {
            LoggerService::info('fn: scheduleHomeRenewalsOcbEmails - No leads found for sending OCB Emails'); 
            return false;
        }

        $renewalsBatchEmail = RenewalsBatchEmails::create([
            'batch' => $batch,
            'status' => ProcessStatusCode::PENDING,
            'total_leads' => $totalLeads,
            'total_sent' => 0,
            'total_bounced' => 0,
            'total_failed' => 0,
            'created_by_id' => $userId 
        ]);


        // get pending leads
        $totalLeads = $this->getPendingOcbLeadsTotalNonMotor($batch, QuoteTypeShortCode::HOM);
        
        // If there are not leads, return false
        if ($totalLeads == 0) {
            LoggerService::info('fn: scheduleHomeRenewalsOcbEmails - No leads found for sending OCB Emails'); 
            return false;
        }

        $renewalsBatchEmail = RenewalsBatchEmails::create([
            'batch' => $batch,
            'status' => ProcessStatusCode::PENDING,
            'total_leads' => $totalLeads,
            'total_sent' => 0,
            'total_bounced' => 0,
            'total_failed' => 0,
            'created_by_id' => $userId 
        ]);


        info('CL: ScheduleHomeRenewalOcbEmails OCB email schedule is started');

        $this->renewalsBatchEmail->update(['status' => ProcessStatusCode::IN_PROGRESS]);

        $renewalsUploadService->scheduleHomeOCB($this->batch, $this->renewalsBatchEmail);

        info('CL: ScheduleHomeRenewalOcbEmails OCB email schedule is completed');
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalsBatchEmail->id))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
        $this->renewalsBatchEmail->update(['status' => ProcessStatusCode::FAILED]);
    }
}
