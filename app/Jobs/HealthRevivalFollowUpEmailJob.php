<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Models\DttRevival;
use App\Models\HealthQuote;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class HealthRevivalFollowUpEmailJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $data = null;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = app(SendEmailCustomerService::class)->sendDttEmail($this->data);
        if ($response == 201) {

            $lead = HealthQuote::where('uuid', $this->data->uuid)->first();
            if ($lead->quote_status_id != QuoteStatusEnum::FollowedUp) {
                $lead->update(['quote_status_id' => QuoteStatusEnum::FollowedUp]);
            }
            $revivalRecord = DttRevival::where('uuid', $this->data->uuid)->first();
            $revivalRecord->increment('follow_up_email_count');
            if ($revivalRecord  && $revivalRecord->follow_up_email_count == 6 && $revivalRecord->previous_health_plan_type) {
                $lead->update(['quote_status_id' => QuoteStatusEnum::Stale]);
            }
            if ($revivalRecord  && $revivalRecord->follow_up_email_count == 3 && !$revivalRecord->previous_health_plan_type) {
                $lead->update(['quote_status_id' => QuoteStatusEnum::Stale]);
            }

            info('healthRevivalFollowUp email is sent  -' . $this->data->customerEmail);
        } else {
            info('healthRevivalFollowUp email is not sent -' . $this->data->customerEmail);
        }
    }
}
