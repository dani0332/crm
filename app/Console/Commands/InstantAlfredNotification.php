<?php

namespace App\Console\Commands;

use App\Enums\ActivityTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Events\InstantAlfredCallbackReminderNotification;
use App\Events\InstantAlfredWhatsappReminderNotification;
use App\Models\Activities;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        $activities = Activities::Where('assignee_id', 968)->Where('source', LeadSourceEnum::INSTANT_ALFRED)
            ->Where('status', 0)
            ->get();
        foreach ($activities as $activity) {
            $currentDate = Carbon::now();
            $hoursDifference = $currentDate->diffInHours(Carbon::parse($activity->due_date));
            if ($hoursDifference === 1 || $hoursDifference >= 3) {

                $modelType = $activity->quote_type_id
                    ? QuoteType::select('code')->find($activity->quote_type_id)
                    : null;
                if ($modelType) {
                    $quoteTypeCode = strtolower($modelType->code);
                    $record = $this->getQuoteObjectBy($quoteTypeCode, $activity->quote_request_id, 'id');
                    if ($modelType->code == QuoteTypeCode::Business) {
                        $path = "quotes/business/$record->uuid";
                    } elseif (checkPersonalQuotes($modelType->code)) {
                        $path = "personal-quotes/$quoteTypeCode/$record->uuid";
                    } else {
                        $path = "quotes/$quoteTypeCode/$record->uuid";
                    }

                    $url = url('/')."/$path";
                    $activity->avtivity_type = 'WHATS_APP';
                    if ($record && $activity->avtivity_type === ActivityTypeEnum::CALL_BACK) {
                        info('InstantAlfred CallBack Reminder '.$hoursDifference.' Notification Send to Advisor '.$record->advisor_id.' And Lead Code is '.$record->code);
                        event(new InstantAlfredCallbackReminderNotification($record->uuid, $record->advisor_id, $url, $record->code));

                    } else {
                        info('InstantAlfred Whatsapp Reminder '.$hoursDifference.' Notification Send to Advisor '.$record->advisor_id.' And Lead Code is '.$record->code);
                        event(new InstantAlfredWhatsappReminderNotification($record->uuid, $record->advisor_id, $url, $record->code));

                    }

                }
            }

        }
    }

}
