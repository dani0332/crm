<?php

namespace App\Jobs\Renewals;

use App\Enums\ProcessStatusCode;
use App\Models\RenewalsUploadLeads;
use App\Services\OtherNonMotorRenewalsUploadService;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessRenewalsUploadUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 1200;
    public $backoff = 10;
    private $renewalsUploadLeadId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($renewalsUploadLeadId)
    {
        $this->renewalsUploadLeadId = $renewalsUploadLeadId;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService, OtherNonMotorRenewalsUploadService $otherNonMotorRenewalsUploadService)
    {
        $renewalsUploadLead = RenewalsUploadLeads::find($this->renewalsUploadLeadId);

        if (! $renewalsUploadLead) {
            return false;
        }

        if ($renewalsUploadLead->quote_type === OtherNonMotorRenewalsUploadService::QUOTE_TYPE) {
            return $otherNonMotorRenewalsUploadService->processUploadUpdate($renewalsUploadLead->id);
        }

        return $renewalsUploadService->processUploadUpdate($renewalsUploadLead->id);
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalsUploadLeadId))->dontRelease()->expireAfter($this->timeout)];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $renewalsUploadLead = RenewalsUploadLeads::find($this->renewalsUploadLeadId);
        $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
        info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
    }
}
