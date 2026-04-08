<?php

namespace App\Jobs;

use App\Enums\EmailStatusTypeEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypes;
use App\Models\EmailStatus;
use App\Services\EmailStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

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
    public function handle(EmailStatusService $emailStatusService)
    {
        try {
            if (DB::getDefaultConnection() !== 'mysql') {
                DB::setDefaultConnection('mysql');
            }
            if (! empty($this->emailData->message_id) && ! empty($this->emailData->status)) {

                $isEmailMessage = EmailStatus::latest()->where('msg_id', $this->emailData->message_id)->first();
                $quoteTypeIds = [QuoteTypes::HOME->id(), QuoteTypes::LIFE->id()];
                if (! empty($isEmailMessage->quote_type_id) && ! empty($isEmailMessage->quote_id) && in_array($isEmailMessage->quote_type_id, $quoteTypeIds)) {
                    if ($isEmailMessage->email_status == ProcessStatusCode::UNSUBSCRIBED) {
                        info('EmailStatusEventJob - status is already unsubscribe-request for msg_id: '.$this->emailData->message_id.' | Time: '.now());

                        return true;
                    }
                    info('EmailStatusEventJob - update status for home quote : msg_id: '.$this->emailData->message_id.' - status: '.$this->emailData->status.' | Time: '.now());
                    $emailStatusService->updateEmailStatus($isEmailMessage, $this->emailData->status);

                    return true;
                }

                $isEmailStatus = EmailStatus::latest()->where('msg_id', $this->emailData->message_id)
                    ->where('email_status', $this->emailData->status)
                    ->first();
                if (! empty($isEmailStatus)) {
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
                        $newEmailStatus->type = $emailStatusData->type ?? EmailStatusTypeEnum::Email;
                        $newEmailStatus->flow_type = $this->emailData->flow_type ?? $emailStatusData->flow_type;
                        $newEmailStatus->save();

                        $emailStatusService->forgetEmailStatusListCache((int) $newEmailStatus->quote_type_id, (int) $newEmailStatus->quote_id);

                        info('EmailStatusEventJob - EmailStatus created for msg_id: '.$this->emailData->message_id.' email_status: '.$newEmailStatus->email_status.' | Time:'.now());
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
        } catch (\Throwable $th) {
            info('EmailStatusEventJob - Error: '.$th->getMessage().' on line: '.$th->getLine().' in file: '.$th->getFile().' | '.PHP_EOL.$th->getTraceAsString());
            throw $th;
        }
    }

}
