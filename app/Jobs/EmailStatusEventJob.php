<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\EmailStatus;
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
        if (! empty($this->data->message_id) && ! empty($this->data->status)) {
            $isEmailStatus = EmailStatus::where('msg_id', $this->data->message_id)
                ->where('email_status', $this->data->status)
                ->exists();

            if ($isEmailStatus) {
                $msg = 'EmailStatus already exists for msg_id: '.$this->data->message_id;
                info($msg);

                return true;
            }
            $emailStatusData = EmailStatus::where('msg_id', $this->data->message_id)->first();
            if (! empty($emailStatusData)) {
                if (! empty($emailStatusData->quoteTypeId) && ! empty($emailStatusData->quoteId)) {
                    $newEmailStatus = new EmailStatus;
                    $newEmailStatus->quote_type_id = $emailStatusData->quoteTypeId;
                    $newEmailStatus->quote_id = $emailStatusData->quoteId;
                    $newEmailStatus->email_address = $this->data->customer_email ?? null;
                    $newEmailStatus->msg_id = $this->data->message_id;
                    $newEmailStatus->email_status = $this->data->status;
                    $newEmailStatus->email_subject = $this->data->subject;
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
