<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Services\EmailServices\LifeEmailService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Services\Logger\LoggerService;
use App\Enums\QuoteTypes;

class SendFICEmailForLife implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $quoteUuid;
    public $tries = 3;
    public $timeout = 15;
    public $backoff = 60;

    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }
  

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lifeFICSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::FIC_LIFE_EMAIL_SWITCH)->first();
        $personalQuote = PersonalQuote::where('uuid', $this->quoteUuid)->first();
        LoggerService::startQuoteLogging(QuoteTypes::LIFE->refId($personalQuote->uuid));
        if ($lifeFICSwitch && $lifeFICSwitch->value == 1) {
            app(LifeEmailService::class)->sendFICEmail($personalQuote);
            LoggerService::info(self::class.' - FIC Life Email sent');
        } else {
            LoggerService::info(self::class.' - FIC Life Email Switch is off');
        }
    }
}
