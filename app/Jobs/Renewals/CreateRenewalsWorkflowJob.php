<?php

namespace App\Jobs\Renewals;

use App\Enums\quoteTypeCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\WorkflowTypeEnum;
use App\Facades\Kyo;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalsUploadLeads;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CreateRenewalsWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 80;
    public $backoff = 360;
    public $tries = 3;

    private $renewalsBatchEmail;

    /**
     * Create a new job instance.
     */
    public function __construct(RenewalsBatchEmails $renewalsBatchEmail)
    {
        $this->renewalsBatchEmail = $renewalsBatchEmail;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('*** CL: CreateRenewalsWorkflowJob create workflow for renewals batch:' . $this->renewalsBatchEmail->batch . ' started ***');

        $response = Kyo::post('/workflows', [
            'quote_type' => quoteTypeCode::Car,
            'type' => WorkflowTypeEnum::RENEWALS,
            'renewal_batch' => $this->renewalsBatchEmail->batch
        ]);

        if(isset($response['success']) && $response['success']) {
            info('workflow created successfully for batch: ' . $this->renewalsBatchEmail->batch);
        } else
        {
            //todo: send email or something
            info('workflow creation failed for batch: ' . $this->renewalsBatchEmail->batch);
        }

        info('*** CL: CreateRenewalsWorkflowJob create workflow for renewals batch:' . $this->renewalsBatchEmail->batch . ' completed ***');
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
        info('workflow creation failed for batch: ' . $this->renewalsBatchEmail->batch);
    }
}
