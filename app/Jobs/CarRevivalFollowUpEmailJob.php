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

class CarRevivalFollowUpEmailJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;

    private $data = null;
    protected $sendEmailCustomerService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $response = $this->sendEmailCustomerService->sendDttEmail($this->data);
        if ($response == 201) {
            DttRevival::where('id', $this->data->id)->increment('follow_up_email_count');
            info('carRevivalFollowUp email is sent  -'.$this->data->customerEmail);
        } else {
            info('carRevivalFollowUp email is not sent -'.$this->data->customerEmail);
        }
    }
}
