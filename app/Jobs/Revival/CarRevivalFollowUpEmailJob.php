<?php

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Models\DttRevival;
use App\Services\ApplicationStorageService;
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

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $data = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('Dtt is not enabled from cms');

            return false;
        }

        $response = app(SendEmailCustomerService::class)->sendDttEmail($this->data);
        if ($response == 201) {
            DttRevival::where('id', $this->data->id)->increment('follow_up_email_count');
            info('CarRevivalFollowUpEmailJob email is sent '.$this->data->uuid.' - '.$this->data->customerEmail);
        } else {
            info('CarRevivalFollowUpEmailJob email not sent '.$this->data->uuid.' - '.$this->data->customerEmail);
        }
    }
}
