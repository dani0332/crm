<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\EmailServices\HomeEmailService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAutomatedHomeRenewalFollowup implements ShouldQueue
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
        $homeAutomatedFollowupSwitch = getAppStorageValueByKey(ApplicationStorageEnums::AUTOMATED_HOME_RENEWAL_FOLLOWUP_SWITCH, useCache: true);

        $personalQuote = PersonalQuote::where('uuid', $this->quoteUuid)->where('quote_type_id', QuoteTypeId::Home)->first();

        if (!$personalQuote) {
            LoggerService::info(self::class." - Personal Quote not found for: {$this->quoteUuid}");
            return;
        }

        LoggerService::startQuoteLogging(QuoteTypes::HOME->refId($personalQuote->uuid));
        
        if ($homeAutomatedFollowupSwitch && $homeAutomatedFollowupSwitch == 1) {
            // Check working hours before sending (8:00 AM to 6:30 PM Dubai time)
            if ($this->isWithinWorkingHours()) {
                app(HomeEmailService::class)->sendAutomatedHomeRenewalFollowup($personalQuote);
                LoggerService::info(self::class." - Automated Home Renewal Followup sent for quote: {$personalQuote->uuid}");
            } else {
                // Reschedule for next working hour
                $nextWorkingTime = $this->getNextWorkingTime();
                self::dispatch($this->quoteUuid)->delay($nextWorkingTime);
                LoggerService::info(self::class." - Rescheduled for next working hours: {$nextWorkingTime} for Home renewal quote: {$personalQuote->uuid}");
            }
        } else {
            LoggerService::info(self::class." - Automated Home Renewal Followup Switch is off for quote: {$personalQuote->uuid}");
        }
    }

    /**
     * Check if current time is within working hours (8:00 AM - 6:30 PM Dubai time)
     */
    private function isWithinWorkingHours(): bool
    {
        $dubaiTime = Carbon::now('Asia/Dubai');
        $workingStart = Carbon::createFromTime(8, 0, 0, 'Asia/Dubai');
        $workingEnd = Carbon::createFromTime(18, 30, 0, 'Asia/Dubai');

        // Set the same date for comparison
        $workingStart->setDate($dubaiTime->year, $dubaiTime->month, $dubaiTime->day);
        $workingEnd->setDate($dubaiTime->year, $dubaiTime->month, $dubaiTime->day);

        return $dubaiTime->between($workingStart, $workingEnd) && !$dubaiTime->isWeekend();
    }

    /**
     * Get next working time (8:00 AM Dubai time, excluding weekends)
     */
    private function getNextWorkingTime(): Carbon
    {
        $dubaiTime = Carbon::now('Asia/Dubai');
        
        // If it's after 6:30 PM or before 8:00 AM, schedule for next 8:00 AM
        $nextWorkingDay = $dubaiTime->copy();
        
        // If it's after working hours today, move to next day
        if ($dubaiTime->hour >= 18 && $dubaiTime->minute >= 30) {
            $nextWorkingDay->addDay();
        }
        
        // Skip weekends
        while ($nextWorkingDay->isWeekend()) {
            $nextWorkingDay->addDay();
        }
        
        // Set to 8:00 AM
        $nextWorkingDay->setTime(8, 0, 0);
        
        return $nextWorkingDay;
    }
}
