<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Services\EmailServices\LifeEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
