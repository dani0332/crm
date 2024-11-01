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
    private $emailData;
    public function __construct($emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (! empty($this->emailData->message_id) && ! empty($this->emailData->status)) {
            $isEmailStatus = EmailStatus::where('msg_id', $this->emailData->message_id)
                ->where('email_status', $this->emailData->status)
                ->exists();
            if ($isEmailStatus) {
                $msg = 'EmailStatus already exists for msg_id: '.$this->emailData->message_id;
                info($msg);

                return true;
            }
            $emailStatusData = EmailStatus::where('msg_id', $this->emailData->message_id)->first();
            if (! empty($emailStatusData)) {
                if (! empty($emailStatusData->quote_type_id) && ! empty($emailStatusData->quote_id)) {
                    $newEmailStatus = new EmailStatus;
                    $newEmailStatus->quote_type_id = $emailStatusData->quote_type_id;
                    $newEmailStatus->quote_id = $emailStatusData->quote_id;
                    $newEmailStatus->email_address = $this->emailData->customer_email ?? $emailStatusData->email_address;
                    $newEmailStatus->msg_id = $this->emailData->message_id;
                    $newEmailStatus->email_status = $this->emailData->status;
                    $newEmailStatus->email_subject = $this->emailData->subject ?? $emailStatusData->email_subject;
                    $newEmailStatus->save();
                    info('EmailStatusEventJob - EmailStatus created for msg_id: '.$this->emailData->message_id.' email_status: '.$newEmailStatus->email_status.' | Time:'.now());

                    return true;
                } else {
                    info('EmailStatusEventJob - quote_type_id not found: msg_id: '.$this->emailData->message_id.' | Time: '.now());
                }

            } else {
                info('EmailStatusEventJob - email data not found for msg_id: '.$this->emailData->message_id);

                return true;
            }
        } else {
            info('EmailStatusEventJob - email data not found');

            return true;
        }
    }
}
