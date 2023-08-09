<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Models\ApplicationStorage;
use App\Services\SIBService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CarLostStatusRejected implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 300;
    private $quote = null;
    private $carLostQuoteLog = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quote, $carLostQuoteLog)
    {
        $this->quote = $quote;
        $this->carLostQuoteLog = $carLostQuoteLog;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $emailTemplateKey = ($this->carLostQuoteLog->quote_status_id == QuoteStatusEnum::CarSold) ? ApplicationStorageEnums::CAR_SOLD_STATUS_REJECTION_TEMPLATE : ApplicationStorageEnums::UNCONTACTABLE_STATUS_REJECTION_TEMPLATE;
        $templateId = ApplicationStorage::where('key_name', $emailTemplateKey)->first()->value;

        $emailData = [
            'uuid' => $this->quote->uuid,
            'reason' => $this->carLostQuoteLog->reason->text,
            'advisor_name' => $this->quote->advisor->name,
            'notes' => $this->carLostQuoteLog->notes,
        ];

        $to = $this->quote->advisor->email;
        $cc = null;

        if ($this->quote->advisor->managers->count()) {
            $cc = implode(',', $this->quote->advisor->managers->pluck('email')->toArray());
        }

        info('before sending Status rejected email for UUID: '.$this->quote->uuid.' to Advisor '.$this->quote->advisor->email);

        SIBService::sendEmailUsingSIB(intval($templateId), $emailData, '', $to, $cc);

        info('status rejected email has been sent');
    }
}
