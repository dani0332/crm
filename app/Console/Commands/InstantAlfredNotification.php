<?php

namespace App\Console\Commands;

use App\Enums\ActivityTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Events\InstantAlfredCallbackReminderNotification;
use App\Events\InstantAlfredWhatsappReminderNotification;
use App\Models\Activities;
use App\Models\ActivityNotificationLogs;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InstantAlfredNotification extends Command
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
            ->where(DB::raw("TIMESTAMPDIFF(HOUR, created_at, '$currentTime')"), '>=', 1)
            ->where(DB::raw("TIMESTAMPDIFF(HOUR, created_at, '$currentTime')"), '<=', 3)
            ->chunk(500, function ($activities) {
                foreach ($activities as $activity) {
                    $modelType = $activity->quote_type_id
                        ? QuoteType::select('code')->find($activity->quote_type_id)
                        : null;

                    if ($modelType && $activity) {
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
                            $notificationType = $activity->activity_type === ActivityTypeEnum::CALL_BACK
                                ? ActivityTypeEnum::CALL_BACK
                                : ActivityTypeEnum::WHATS_APP;

                            $eventClass = $activity->activity_type === ActivityTypeEnum::CALL_BACK
                                ? InstantAlfredCallbackReminderNotification::class
                                : InstantAlfredWhatsappReminderNotification::class;

                            if ($eventClass) {
                                ActivityNotificationLogs::create([
                                    'activity_id' => $activity->id,
                                    'advisor_id' => $activity->assignee_id,
                                    'notification_type' => $notificationType,
                                ]);
                                info("InstantAlfred {$notificationType} Reminder Notification Send to Advisor {$record->advisor_id} And Quote Code is {$record->code}");
                                event(new $eventClass($record->uuid, $record->advisor_id, $url, $record->code));
                            }
                        }
                    }
                }
            });
    }

}
