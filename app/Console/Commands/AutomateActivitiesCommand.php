<?php

namespace App\Console\Commands;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Activities;
use App\Models\ActivitySchedule;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\Team;
use App\Models\TravelQuote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutomateActivitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ActivitiesAutomate:cron';

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
        info('------------------- Automate Activities Command Started At: '.now().' -------------------');

        $quoteTypeDetails = [
            CarQuote::class => [
                'eligible_for_automate' => false,
                'quote_type_id' => QuoteTypeId::Car,
            ],
            HomeQuote::class => [
                'eligible_for_automate' => true,
                'quote_type_id' => QuoteTypeId::Home,
                'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::HOME_RENEWALS])->first()->id,
            ],
            HealthQuote::class => [
                'eligible_for_automate' => true,
                'quote_type_id' => QuoteTypeId::Health,
                'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::RM_RENEWALS])->first()->id,
            ],
            LifeQuote::class => [
                'eligible_for_automate' => false,
                'quote_type_id' => QuoteTypeId::Life,
            ],
            BusinessQuote::class => [
                'eligible_for_automate' => true,
                'quote_type_id' => QuoteTypeId::Business,
                'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::CORPLINE_RENEWALS])->first()->id,
            ],
            TravelQuote::class => [
                'eligible_for_automate' => false,
                'quote_type_id' => QuoteTypeId::Travel,
            ],
            PersonalQuote::class => [
                'eligible_for_automate' => true,
                'multiple_lobs' => true,
                'quote_type_id' => [
                    QuoteTypeId::Pet,
                    QuoteTypeId::Cycle,
                    QuoteTypeId::Yacht,
                ],
                'quote_type_details' => [
                    QuoteTypeId::Pet => [
                        'quote_type_id' => QuoteTypeId::Pet,
                        'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::PET_RENEWALS])->first()->id,
                    ],
                    QuoteTypeId::Cycle => [
                        'quote_type_id' => QuoteTypeId::Cycle,
                        'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::CYCLE_RENEWALS])->first()->id,
                    ],
                    QuoteTypeId::Yacht => [
                        'quote_type_id' => QuoteTypeId::Yacht,
                        'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::YACHT_RENEWALS])->first()->id,
                    ],
                ],
            ],
        ];

        foreach ($quoteTypeDetails as $quoteClass => $quoteTypeDetail) {
            info('------------------- ActivitiesAutomate - Updating Cold Activities for : '.$quoteClass.' -------------------');
            $coldActivitiesCount = 0;
            Activities::where(function ($query) use ($quoteTypeDetail) {
                if (is_array($quoteTypeDetail['quote_type_id'])) {
                    $query->whereIn('activities.quote_type_id', $quoteTypeDetail['quote_type_id']);
                } else {
                    $query->where('activities.quote_type_id', $quoteTypeDetail['quote_type_id']);
                }
            })
                ->where('due_date', '<', Carbon::now())
                ->where('status', false)
                ->where('activities.is_cold', false)
                ->select('activities.id', 'quote_request_id')
                ->chunkById(1000, function ($activities) use ($quoteClass, &$coldActivitiesCount) {
                    $ids = $activities->pluck('id')->toArray();
                    $coldActivitiesCount += count($ids);
                    Activities::whereIn('id', $ids)->update(['is_cold' => true]);

                    if (in_array($quoteClass, [HealthQuote::class, HomeQuote::class, BusinessQuote::class, PersonalQuote::class])) {
                        $quoteIds = $activities->pluck('quote_request_id')->unique()->toArray();
                        $quoteClass::whereIn('id', $quoteIds)->update(['is_cold' => true]);

                        if ($quoteClass != PersonalQuote::class) {

                            $quotesCodes = $quoteClass::whereIn('id', $quoteIds)->select('code')->get()->toArray();
                            PersonalQuote::whereIn('code', $quotesCodes)->update(['is_cold' => true]);
                        }
                    }
                });

            info('------------------- ActivitiesAutomate - Updated Cold Activities for : '.$quoteClass.' - count: '.$coldActivitiesCount.' -------------------');

            if ($quoteTypeDetail['eligible_for_automate'] == true) {
                info('------------------- ActivitiesAutomate - Fetching : '.$quoteClass.' Quotes for create follow-up Activities -------------------');
                $followupCount = 0;
                $quoteClass::whereHas('activities', function ($activityQuery) {
                    $activityQuery->where('due_date', '<', Carbon::now());
                    $activityQuery->where('status', true);
                })
                    ->with('activities')
                    ->chunkById(1000, function ($quoteDetails) use ($quoteTypeDetail, &$followupCount) {

                        $activitiesToCreate = [];
                        $advisors = User::with('usersroles', 'teams')
                            ->whereIn('id', $quoteDetails->pluck('advisor_id')->toArray())
                            ->get()
                            ->keyBy('id');

                        $roleIds = $advisors->flatMap(function ($advisor) {
                            return $advisor->usersroles;
                        })->unique('id')->pluck('id')->toArray();

                        $teamIds = $advisors->flatMap(function ($advisor) {
                            return $advisor->teams;
                        })->unique('id')->pluck('id')->toArray();

                        $schedules = ActivitySchedule::whereIn('quote_type_id',
                            is_array($quoteTypeDetail['quote_type_id']) ? $quoteTypeDetail['quote_type_id'] : [$quoteTypeDetail['quote_type_id']])
                            ->whereIn('role_id', $roleIds)
                            ->whereIn('team_id', $teamIds)
                            ->get();

                        foreach ($quoteDetails as $quoteDetail) {
                            if (! empty($quoteDetail->advisor_id)) {

                                $advisorDetails = $advisors[$quoteDetail->advisor_id];
                                $getQuoteType = isset($quoteTypeDetail['multiple_lobs']) ?
                                    (isset($quoteTypeDetail['quote_type_details'][$quoteDetail->quote_type_id]) ?
                                        $quoteTypeDetail['quote_type_details'][$quoteDetail->quote_type_id]['quote_type_id'] :
                                        null) :
                                    $quoteTypeDetail['quote_type_id'];

                                $lastActivity = $quoteDetail->activities->sortByDesc('id')->first();
                                $quoteDetail->activities = $quoteDetail->activities->where('status', true)->sortByDesc('id');

                                $activityCreationAllowed = true;
                                if ($lastActivity->is_cold || $lastActivity->status == 0) {
                                    $activityCreationAllowed = false;
                                }

                                $scheduledActivitiesIDs = $quoteDetail->activities
                                    ->pluck('activity_schedule_id')
                                    ->unique()
                                    ->toArray();

                                $activitySchedules = $schedules->where('quote_status_id', $quoteDetail->quote_status_id)
                                    ->where('quote_type_id', $getQuoteType)
                                    ->whereIn('role_id', $advisorDetails->usersroles->pluck('id'))
                                    ->whereIn('team_id', $advisorDetails->teams->pluck('id'))
                                    ->when(! empty($scheduledActivitiesIDs), function ($previousSchedule) use ($scheduledActivitiesIDs) {
                                        return $previousSchedule->whereNotIn('id', $scheduledActivitiesIDs);
                                    })
                                    ->when($quoteDetail->source == LeadSourceEnum::RENEWAL_UPLOAD, function ($query) use ($quoteDetail, $quoteTypeDetail) {
                                        $renewalTeamID = isset($quoteTypeDetail['multiple_lobs']) ?
                                            $quoteTypeDetail['quote_type_details'][$quoteDetail->quote_type_id]['renewal_team'] : $quoteTypeDetail['renewal_team'];

                                        return $query->where('team_id', $renewalTeamID ?? null);
                                    })->first();

                                if ($activitySchedules && $activityCreationAllowed) {
                                    $activitiesToCreate[] = [
                                        'title' => $activitySchedules->name,
                                        'description' => $activitySchedules->description,
                                        'quote_request_id' => $quoteDetail->id,
                                        'quote_type_id' => $getQuoteType,
                                        'status' => 0,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                        'assignee_id' => $quoteDetail->advisor_id,
                                        'uuid' => generateUuid(),
                                        'due_date' => addDaysExcludeWeekend($activitySchedules->due_days, $quoteDetail->activities->first()->due_date ?? now()),
                                        'client_name' => $quoteDetail->first_name.' '.$quoteDetail->last_name,
                                        'client_email' => $quoteDetail->email,
                                        'quote_uuid' => $quoteDetail->uuid,
                                        'quote_status_id' => $quoteDetail->quote_status_id,
                                        'activity_schedule_id' => $activitySchedules->id,
                                        'source' => LeadSourceEnum::IMCRM,
                                    ];
                                }
                            }
                        }

                        // bulk create
                        if (! empty($activitiesToCreate)) {
                            $followupCount += count($activitiesToCreate);
                            Activities::insert($activitiesToCreate);
                        }
                    });
                info('------------------- Follow-up Activities created for : '.$quoteClass.' - count: '.$followupCount.' -------------------');
            }
        }
        info('------------------- Automate Activities Command Finished At: '.now().' -------------------');
    }
}
