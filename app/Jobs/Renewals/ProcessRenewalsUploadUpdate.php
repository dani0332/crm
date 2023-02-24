<?php

namespace App\Jobs\Renewals;

use App\Enums\ProcessStatusCode;
use App\Models\RenewalsUploadLeads;
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
    protected $renewalsUploadLead;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RenewalsUploadLeads $renewalsUploadLead)
    {
        $this->renewalsUploadLead = $renewalsUploadLead;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {
        return $renewalsUploadService->processUploadUpdate($this->renewalsUploadLead);
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalsUploadLead->id))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $this->renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
        info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
    }
}
