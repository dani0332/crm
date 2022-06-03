<?php

namespace App\Jobs;

use App\Models\RenewalsUploadLeads;
use App\Services\CheckAmlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\RenewalsUploadService;
use DB;

class CheckAMLJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $firstName;
    protected $lastName;
    protected $quoteRequestId;
    protected $quoteTypeId;
    protected $isEmailSendingEnabled;
    protected $yob;
    protected $companyName;
    protected $amlService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($firstName, $lastName, $quoteRequestId, $quoteTypeId, $isEmailSendingEnabled, $yob, $companyName)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->quoteRequestId = $quoteRequestId;
        $this->quoteTypeId = $quoteTypeId;
        $this->isEmailSendingEnabled = $isEmailSendingEnabled;
        $this->yob = $yob;
        $this->companyName = $companyName;
        $this->amlService = new CheckAmlService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Sending request with data to create renewal and normal quote
            $this->amlService->checkAml($this->firstName, $this->lastName, $this->quoteRequestId, $this->quoteTypeId, $this->isEmailSendingEnabled, $this->yob, $this->companyName);
        } catch (\Exception $e) {
            return $e;
        } finally {
            DB::disconnect('mysql');
        }
    }
}
