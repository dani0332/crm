<?php

namespace App\Console\Commands;

use App\Models\BusinessQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use Illuminate\Console\Command;

class AutomateActivitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AutomateActivitiesCommand:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This runs to check if any done activities are available with no change in lead quote status then assign new activities accordingly';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        return true;
        // When create activities automatic we have 2 cases
        // Case 1 : Previously created activity marked as done, and no change in Status
        // Case 2 : Previously created activity not done

        // First fetch records which have activities 
        // If any already created activities done and quote_status_modified date is greater than due_date then assign new activities to the same lead.

        $eligibleQuoteTypes = [
            HealthQuote::class,
            BusinessQuote::class,
            HomeQuote::class,
            PersonalQuote::class
        ];

        info("------------------- Automate Activities Command Started At: " . now() . " -------------------");

        foreach ($eligibleQuoteTypes as $eligibleQuoteType) {

            info("------------------- Fetching : " . $eligibleQuoteType . " Records -------------------");
            $getRecords = $eligibleQuoteType::whereHas('activities')
            ->with('activities', function($activity){
                $activity->orderBy('created_at', 'desc')->first();
            })
            ->where('id', '48')
            // ->where('quote_status_date', '<', 'due_date')
            ->limit(2)->get();
            dd($getRecords->toArray());

            // whereNotIn('quote_status_id', $skipStatus)
            //     ->where('updated_at', '<', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-30 days')))
            //     ->when($eligibleQuoteType == BusinessQuote::class, function ($businessQuote) {
            //         $businessQuote->whereNot('business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            //     })
            //     ->when($eligibleQuoteType == PersonalQuote::class, function ($personalQuote) {
            //         $personalQuote->whereIn('quote_type_id', [QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle]);
            //     })->chunkById(1000, function ($quoteDetails) {
            //         foreach ($quoteDetails as $quoteDetail) {
            //             $quoteDetail->update([
            //                 'stale_at' => now()
            //             ]);
            //         }
            //     });
            info("------------------- Updated : " . $eligibleQuoteType . " -------------------");
        }
    }
}
