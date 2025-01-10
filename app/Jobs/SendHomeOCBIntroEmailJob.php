<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Services\EmailServices\HomeEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendHomeOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 10;
    /**
     * Create a new job instance.
     */
    public function __construct(public $quoteUuid, public $previousAdvisor = null) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $homeOCBSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS_SWITCH)->first();
        $personalQuote = PersonalQuote::where('uuid', $this->quoteUuid)->first();
        if ($homeOCBSwitch && $homeOCBSwitch->value == 1) {
            app(HomeEmailService::class)->sendHomeOCBIntroEmail($personalQuote);
            info(self::class." - Home OCB Automated Followups Switch is on - Ref ID: {$personalQuote->uuid} | Time: ".now());
        } else {
            info(self::class." - Home OCB Automated Followups Switch is off - Ref ID: {$personalQuote->uuid} | Time: ".now());
        }
    }
}
