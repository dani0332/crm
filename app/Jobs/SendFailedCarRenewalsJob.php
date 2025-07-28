<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Logger\LoggerService;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;
use App\Services\EmailServices\CarEmailService;

class SendFailedCarRenewalsJob implements ShouldQueue
{
    use Queueable;
    public $failedQuotes;
    /**
     * Create a new job instance.
     */
    public function __construct($failedQuotes)
    {
        $this->failedQuotes = $failedQuotes;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $workflowSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
    
        if ($workflowSwitch) {
            app(CarEmailService::class)->sendFailedCarRenewals($this->failedQuotes);
            
            LoggerService::info(self::class.' - Car PCP OCB  Switch is on | Time: '.now().' | Ref-ID: '.$carLead->uuid);
        } else {
            LoggerService::info(self::class.' - Car OCB PDP  Switch is off | Time: '.now().' | Ref-ID: '.$carLead->uuid);
        }
      
    }
}
