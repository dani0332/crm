<?php

namespace App\Jobs;

use App\Models\EmailStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EmailStatusEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $tries = 3;

    public $timeout = 15;
    public $backoff = 300;
    private $data;
    public function __construct($emailData)
    {
        $this->data = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {

        info('data: '.json_encode($this->data));

        info("messgageID: ".$this->data->message_id);
        info("status: ".$this->data->status);

        if (! empty($this->data->message_id) && ! empty($this->data->status)) {
            $isEmailStatus = EmailStatus::where('msg_id', $this->data->message_id)
                ->where('email_status', $this->data->status)
                ->exists();
                info('isEmailStatus: '.json_encode($isEmailStatus));
            if ($isEmailStatus) {
                $msg = 'EmailStatus already exists for msg_id: '.$this->data->message_id;
                info($msg);
                return true;
            }
            $emailStatusData = EmailStatus::where('msg_id', $this->data->message_id)->first();
            info('emailStatusData: '.json_encode($emailStatusData));
            if (! empty($emailStatusData)) {
                info('emailStatusData: 1');
                if (! empty($emailStatusData->quote_type_id) && ! empty($emailStatusData->quote_id)) {
                    info('emailStatusData: 2');
                    $newEmailStatus = new EmailStatus;
                    $newEmailStatus->quote_type_id = $emailStatusData->quote_type_id;
                    $newEmailStatus->quote_id = $emailStatusData->quote_id;
                    $newEmailStatus->email_address = $this->data->customer_email ?? $emailStatusData->email_address;
                    $newEmailStatus->msg_id = $this->data->message_id;
                    $newEmailStatus->email_status = $this->data->status;
                    $newEmailStatus->email_subject = $this->data->subject ?? $emailStatusData->email_subject;
                    $newEmailStatus->save();
                    info('EmailStatusEventJob - EmailStatus created for msg_id: '.$this->data->message_id.' email_status: '.$newEmailStatus->email_status.' | Time:'.now());
                    return true;
                }

            } else {
                info('EmailStatusEventJob - email data not found for msg_id: '.$this->data->message_id);
                return true;
            }
        } else {
            info('EmailStatusEventJob - email data not found');

            return true;
        }
    }
}
