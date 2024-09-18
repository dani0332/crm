<?php

namespace App\Jobs;

use App\Models\DttRevival;
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
            DttRevival::where('id', $this->data->id)->increment('follow_up_email_count');
            info('healthRevivalFollowUp email is sent  -'.$this->data->customerEmail);
        } else {
            info('healthRevivalFollowUp email is not sent -'.$this->data->customerEmail);
        }
    }
}
