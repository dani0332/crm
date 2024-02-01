<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypeId;
use App\Models\Activities;
use App\Models\ActivitySchedule;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Models\User;
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
    protected $description = "Check if the follow-up activity's due date has passed, shall automatically classify as Cold Activity. Also, If any done activities with no changes in quote status then assign new follow-up activities to the relevent advisor";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("------------------- Automate Activities Command Started At: " . now() . " -------------------");

        $allQuoteTypes = [
            CarQuote::class,
            HomeQuote::class,
            HealthQuote::class,
            LifeQuote::class,
            BusinessQuote::class,
            TravelQuote::class,
            PersonalQuote::class
        ];

        $eligibleQuoteTypes = [
            HealthQuote::class,
            BusinessQuote::class,
            HomeQuote::class,
            PersonalQuote::class
        ];

        $quoteTypeIDs = [
            HealthQuote::class => QuoteTypeId::Health,
            BusinessQuote::class => QuoteTypeId::Business,
            HomeQuote::class => QuoteTypeId::Home
        ];

        foreach ($allQuoteTypes as $allQuoteType) {

            info("------------------- Updating Cold Activities for : " . $allQuoteType . " -------------------");
            $allQuoteType::whereHas('activities', function($activityQuery){
                $activityQuery->where('due_date', '<', now());
                $activityQuery->where('status', false);
            })->with(['activities' => function($activities){
                $activities->where('due_date', '<', now());
                $activities->where('status', false);
            }])
            ->chunkById(1000, function ($quoteDetails) {
                foreach ($quoteDetails as $quoteDetail) {
                    $activitiesIDs = $quoteDetail->activities->pluck('id');
                    Activities::whereIn('id', $activitiesIDs)->update(['is_cold' => true]);
                }
            });
            info("------------------- Updated Cold Activities for : " . $allQuoteType . " -------------------");

        }

        foreach ($eligibleQuoteTypes as $eligibleQuoteType) {
            info("------------------- Fetching : " . $eligibleQuoteType . " Quotes for create follow-up Activities -------------------");
            $eligibleQuoteType::whereHas('activities', function($activityQuery){
                $activityQuery->where('due_date', '<', now());
                $activityQuery->where('status', true);
            })
            ->with(['activities' => function($activities){
                $activities->where('due_date', '<', now());
                $activities->where('status', true);
                $activities->orderBy('created_at', 'desc')->get();
            }])
            // Should be removed
            ->where('id', 3027)
            ->chunkById(1000, function($quotes) use ($eligibleQuoteType, $quoteTypeIDs) {
                foreach ($quotes as $quote) {
                    if(!empty($quote->advisor_id)) {
                        $advisorDetails = User::with('usersroles', 'teams')->where('id', $quote->advisor_id)->first();
                        $getQuoteType = (in_array($eligibleQuoteType, array_keys($quoteTypeIDs))) ? $quoteTypeIDs[$eligibleQuoteType] : $quote->quote_type_id;
                        $scheduledActivitiesCreatedIDs = collect($quote->activities->pluck('activity_schedule_id'))
                        ->unique()->filter(function($filter){
                            return !is_null($filter);
                        })->toArray();

                        // Should be fetch as sorting order
                        $fetchingActivitySchedules = ActivitySchedule::where([
                            'quote_type_id' => $getQuoteType,
                            'quote_status_id' => $quote->quote_status_id
                        ])
                        ->whereIn('role_id', $advisorDetails->usersroles->pluck('id'))
                        ->whereIn('team_id', $advisorDetails->teams->pluck('id'))
                        ->when(!empty($scheduledActivitiesCreatedIDs), function($previousSchedule) use ($scheduledActivitiesCreatedIDs) {
                            $previousSchedule->whereNotIn('id', $scheduledActivitiesCreatedIDs);
                        })
                        ->get();

                        dd($fetchingActivitySchedules->toArray());

                        // Follow up activites due date should be count last activity created date
                        Activities::create([
                            'title' => $fetchingActivitySchedules->name,
                            'description' => $fetchingActivitySchedules->description,
                            'quote_request_id' => $quote->id,
                            'quote_type_id' => $getQuoteType,
                            'status' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'assignee_id' => $quote->advisor_id,
                            'uuid' => generateUuid(),
                            'due_date' => addDaysExcludeWeekend($fetchingActivitySchedules->due_days),
                            'client_name' => $quote->first_name.' '.$quote->last_name,
                            'client_email' => $quote->email,
                            'quote_uuid' => $quote->uuid,
                        ]);
                    }
                }
            });

            info("------------------- Follow-up Activities created for : " . $eligibleQuoteType . " -------------------");
        }

        info("------------------- Automate Activities Command Finished At: " . now() . " -------------------");

        // When create activities automatic we have 2 cases
        // Case 1 : Previously created activity marked as done, and no change in Status
        // Case 2 : Previously created activity not done

        // First fetch records which have activities 
        // If any already created activities done and quote_status_modified date is greater than due_date then assign new activities to the same lead.
        
    }
}
