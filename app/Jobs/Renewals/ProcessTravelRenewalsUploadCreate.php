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

class ProcessTravelRenewalsUploadCreate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 30;
    public $backoff = 90;
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
     * @return bool
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {
        return $renewalsUploadService->travelProcessUploadCreate($this->renewalsUploadLead);
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
        info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
        $this->renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
    }
}
