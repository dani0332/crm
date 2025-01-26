<?php

namespace App\Jobs\CarLost;

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
use Throwable;

/**
 * send reminder email for uncontactable renewal batches submission to advisors
 */
class UnconSubmissionReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $backoff = 360;
    public $tries = 3;

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
        info('UnconSubmissionReminder - Uncontactable Submission reminder job started');

        // get next uncontactable batch
        $upcomingBatch = RenewalBatchRepository::getUpcomingBatch(QuoteStatusEnum::Uncontactable);

        if (! isset($upcomingBatch->id)) {
            info('UnconSubmissionReminder - No upcoming batch available for uncontactable resubmission reminder. no need to send email');

            return true;
        }

        info('UnconSubmissionReminder - Upcoming batch found: '.$upcomingBatch->name.' with deadline date: '.$upcomingBatch->deadline->deadline_date);

        // include next 3 more batches for information
        $nextBatches = RenewalBatch::whereHas('deadline', function ($q) use ($upcomingBatch) {
            $q->where('quote_status_id', QuoteStatusEnum::Uncontactable)
                ->whereDate('deadline_date', '>', $upcomingBatch->deadline->deadline_date);
        })->with(['deadline' => function ($q) use ($upcomingBatch) {
            $q->where('quote_status_id', QuoteStatusEnum::Uncontactable)
                ->whereDate('deadline_date', '>', $upcomingBatch->deadline->deadline_date);
        }])->limit(3)->get();

        $emailData['batches'][] = [
            'batch' => $upcomingBatch->name,
            'deadline_date' => Carbon::parse($upcomingBatch->deadline->deadline_date)->format('jS M Y'),
            'highlight' => true,
        ];

        foreach ($nextBatches as $batch) {
            $emailData['batches'][] = [
                'batch' => $batch->name,
                'deadline_date' => Carbon::parse($batch->deadline->deadline_date)->format('jS M Y'),
                'highlight' => false,
            ];
        }

        $teamsGroups = [
            [CarTeamType::RENEWALS],
            [CarTeamType::BDM, CarTeamType::SBDM],
            [CarTeamType::MOTOR_CORPLINE_RENEWALS],
        ];

        $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::UNCON_RENEWALS_REMINDER_TEMPLATE)->value('value');

        foreach ($teamsGroups as $teams) {
            info('UnconSubmissionReminder - Getting advisors for teams: '.implode(',', $teams));

            $advisors = User::whereHas('teams', function ($q) use ($teams) {
                $q->whereIn('name', $teams);
            })->whereHas('roles', function ($q) {
                $q->whereIn('name', [RolesEnum::CarAdvisor]);
            })->activeUser()->with(['managers' => function ($q) {
                $q->where('is_active', 1);
            }])->get();

            if (count($advisors) == 0) {
                info('UnconSubmissionReminder - No advisors found for teams: '.implode(',', $teams).'. No need to send email');

                continue;
            }

            // extract unique manager emails
            $managers = $advisors->pluck('managers.*.email')
                ->push('april.pascual@insurancemarket.ae')
                ->flatten()->unique()->toArray();

            $to = implode(',', $advisors->pluck('email')->unique()->toArray());
            $cc = implode(',', $managers);

            info('UnconSubmissionReminder - Sending Uncontactable Submissions reminder email started');

            SIBService::sendEmailUsingSIB(intval($templateId), $emailData, '', $to, $cc);

            info('UnconSubmissionReminder - Uncontactable Submission reminder email completed');
        }
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        info('CL: '.get_class().' FN: failed. Uncontactable submission reminder Job Failed. Error: '.$exception->getMessage());
    }
}
