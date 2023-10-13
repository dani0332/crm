<?php

namespace App\Jobs\CarLost;

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
    public $timeout = 40;
    public $backoff = 360;
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
            'reason' => $this->carLostQuoteLog->reason->text ?? null,
            'advisor_name' => $this->quote->advisor->name,
            'notes' => $this->carLostQuoteLog->notes,
        ];

        $to = $this->quote->advisor->email;

        $cc = [];

        if (
            ($rejectionEmailCc = ApplicationStorage::where('key_name', ApplicationStorageEnums::CAR_LOST_REJECTION_EMAIL_CC)->first())
            && ! empty($rejectionEmailCc->value)
        ) {
            $cc[] = $rejectionEmailCc->value;
        }

        if ($this->quote->advisor->managers->count()) {
            $cc = array_merge($cc, $this->quote->advisor->managers->pluck('email')->toArray());
        }

        if (count($cc)) {
            $cc = implode(',', $cc);
        }

        info('CarLostStatusRejected - before sending Status rejected email for UUID: '.$this->quote->uuid.' to Advisor '.$this->quote->advisor->email);

        SIBService::sendEmailUsingSIB(intval($templateId), $emailData, '', $to, $cc);

        info('CarLostStatusRejected - status rejected email has been sent');
    }
}
