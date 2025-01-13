<?php

namespace App\Console\Commands;

use App\Enums\ActivityTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Events\CallBackNotifications;
use App\Models\Activities;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CallBackNotification extends Command
{
    use GenericQueriesAllLobs;
    use TeamHierarchyTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'InstantAlfredNotification:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'InstantAlfred CallBack and Whatsapp Notification';

    protected $allowedQuoteTypeIds = [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Bike];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currentTime = Carbon::now()->toDateTimeString();
        info("Notification Reminder Job Started {$currentTime}");
        Activities::where('source', LeadSourceEnum::INSTANT_ALFRED)
            ->where('status', 0)
            ->where(function ($query) use ($currentTime) {
                $query->where(DB::raw("TIMESTAMPDIFF(MINUTE, created_at, '$currentTime')"), '=', 60)
                    ->orWhere(DB::raw("TIMESTAMPDIFF(MINUTE, created_at, '$currentTime')"), '=', 120);
            })
            ->whereIn('quote_type_id', $this->allowedQuoteTypeIds)
            ->chunk(500, function ($activities) {
                foreach ($activities as $activity) {
                    $modelType = $activity->quote_type_id
                        ? QuoteType::select('code')->find($activity->quote_type_id)
                        : null;

                    if ($modelType && $activity && $this->isAllowedQuoteType($modelType->code)) {
                        $quoteTypeCode = strtolower($modelType->code);
                        $record = $this->getQuoteObjectBy($quoteTypeCode, $activity->quote_request_id, 'id');
                        if ($record) {
                            if ($modelType->code == QuoteTypeCode::Business) {
                                $path = "quotes/business/$record->uuid";
                            } elseif (checkPersonalQuotes($modelType->code)) {
                                $path = "personal-quotes/$quoteTypeCode/$record->uuid";
                            } else {
                                $path = "quotes/$quoteTypeCode/$record->uuid";
                            }

                            $url = url('/')."/$path";

                            // Define notification types and events based on activity type
                            $notificationType = strtoupper($activity->activity_type) === ActivityTypeEnum::CALL_BACK
                                ? ActivityTypeEnum::CALL_BACK
                                : ActivityTypeEnum::WHATS_APP;

                            if ($notificationType) {
                                if (isset($activity->id)) {
                                    $activity->reminders_sent += 1;
                                    $activity->save();
                                }
                                if (strtoupper($notificationType) === ActivityTypeEnum::CALL_BACK) {
                                    $title = 'InstantAlfred CallBack Reminder';
                                    $message = 'Urgent reminder callback request for ';
                                } else {
                                    $title = 'InstantAlfred Whatsapp Reminder';
                                    $message = 'Urgent reminder Whatsapp request for ';
                                }

                                info("InstantAlfred {$notificationType} Reminder Notification Send to Advisor {$record->advisor_id} And Quote Code is {$record->code}");
                                event(new CallBackNotifications($record->uuid, $record->advisor_id, $url, $record->code, $title, $message));
                            }
                        }
                    }
                }
            });
    }

    private function isAllowedQuoteType($quoteTypeCode)
    {
        return in_array($quoteTypeCode, [
            QuoteTypeCode::Car,
            QuoteTypeCode::Travel,
            QuoteTypeCode::Bike,
        ]);
    }

}
