<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Services\EmailServices\SavingsEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSavingsOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(public $quoteUuid, public $previousAdvisor = null)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $savingsOCBSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::SAVINGS_OCB_SWITCH)->first();
        $personalQuote = PersonalQuote::where('uuid', $this->quoteUuid)->first();

        if ($savingsOCBSwitch && $savingsOCBSwitch->value == 1) {
            app(SavingsEmailService::class)->sendSavingsOCBIntroEmail($personalQuote);
            LoggerService::info(self::class.' - Savings OCB Switch is on');
        } else {
            LoggerService::info(self::class.' - Savings OCB Switch is off');
        }

        // For now, just log that the OCB email job was triggered
        LoggerService::info(self::class.' - Savings OCB Email job executed for quote: '.$this->quoteUuid);
    }
}
