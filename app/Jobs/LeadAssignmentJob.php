<?php

namespace App\Jobs;

use App\Models\HealthQuote;
use App\Services\LeadAllocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Facades\Log;
use DB;

class LeadAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $leadId;
    protected $leadAllocationService;
    protected $lead;

    public $maxTries = 5;
    public $timeout = 300;
    public $backoff = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($leadId)
    {
        $this->leadId = $leadId;
        $this->leadAllocationService = new LeadAllocationService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->lead = HealthQuote::where('id', '=', $this->leadId)->first();
            info('Lead Assignment Job Started for lead: ' . $this->lead->uuid);

            $availableUser = $this->leadAllocationService->getNextAssignableUserId($this->lead);
            if($availableUser)
            {
                $this->leadAllocationService->assignLead($this->lead, $availableUser['user_id'], false);
            }
            else
            {
                info('No user available for lead: ' . $this->lead->uuid);
            }

        } catch (\Exception $e) {
            Log::info("message: " . $e->getMessage());
            if ($this->attempts() < 4) {
                $delayInSeconds = 5 * 60;
                $this->release($delayInSeconds);
            }
        } finally {
            DB::disconnect('mysql');
        }
    }
}
