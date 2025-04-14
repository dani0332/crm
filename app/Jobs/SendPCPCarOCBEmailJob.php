<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EmailServices\CarEmailService;

class SendPCPCarOCBEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 10;
    /**
     * Create a new job instance.
     */
    public function __construct(public $quoteUuid) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::startQuoteLogging($this->quoteUuid);
        $homeOCBSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::MOTOR_PCP_OCB_SWITCH)->first();
        $personalQuote = CarQuote::where('uuid', $this->quoteUuid)->first();
        if ($homeOCBSwitch && $homeOCBSwitch->value == 1) {
            app(CarEmailService::class)->sendPCPOCBIntroEmail($personalQuote);
            info(self::class.' - Car PCP OCB  Switch is on | Time: '.now());
        } else {
            info(self::class.' - Car OCB PDP  Switch is off | Time: '.now());
        }
        LoggerService::endLogging();
    }
}
