<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarTeamType;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\ApplicationStorage;
use App\Models\RenewalBatch;
use App\Models\User;
use App\Repositories\RenewalBatchRepository;
use App\Services\SIBService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * send reminder email for uncontactable renewal batches submission to advisors
 */
class UnconSubmissionReminder //implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //get next uncontactable batch
        $upcomingBatch = RenewalBatchRepository::getUpcomingBatch(QuoteStatusEnum::Uncontactable);

        if(!isset($upcomingBatch->id))
        {
            info('No upcoming batch available for uncontactable resubmission reminder. no need to send email');
            return true;
        }

        //include next 3 more batches for information
        $nextBatches = RenewalBatch::whereHas('deadline', function($q) use($upcomingBatch) {
                $q->where('quote_status_id', QuoteStatusEnum::Uncontactable)
                ->whereDate('deadline_date', '>', $upcomingBatch->deadline->deadline_date);
            })->with(['deadline' => function($q) use($upcomingBatch) {
                $q->where('quote_status_id', QuoteStatusEnum::Uncontactable)
                ->whereDate('deadline_date', '>', $upcomingBatch->deadline->deadline_date);
            }])
            ->limit(3)->get();

        $emailData['batches'][] = [
            'batch' => $upcomingBatch->name,
            'deadline_date' => Carbon::parse($upcomingBatch->deadline->deadline_date)->format('jS M Y'),
            'highlight' => true
        ];

        foreach ($nextBatches as $batch)
        {
            $emailData['batches'][] = [
                'batch' => $batch->name,
                'deadline_date' => Carbon::parse($batch->deadline->deadline_date)->format('jS M Y'),
                'highlight' => false
            ];
        }

        $advisors = User::whereHas('teams', function($q){
            $q->whereIn('name', [CarTeamType::RENEWALS, CarTeamType::BDM, CarTeamType::SBDM, CarTeamType::MOTOR_CORPLINE_RENEWALS]);
        })->whereHas('roles', function($q){
            $q->where('name', RolesEnum::CarAdvisor);
        })->with(['managers'])->get();

        $to = implode(',', $advisors->pluck('email')->toArray());
        $cc = implode(',', $advisors->pluck('managers.*.email')->unique()->flatten()->all());

        $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::UNCON_RENEWALS_REMINDER_TEMPLATE)->value('value');
        
        info('Sending Uncontactable Submissions reminder email');

        SIBService::sendEmailUsingSIB(intval($templateId), $emailData, '', $to, $cc);

        info('Uncontactable Submission reminder email is sent');
    }
}
