<?php

namespace App\Services;

use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\EmailStatus;
use App\Models\HealthQuote;

class EmailStatusService extends BaseService
{
    public function getEmailStatus($quoteTypeId, $quoteId)
    {
        return EmailStatus::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function addEmailStatus($emailData, $messageId, $emailSubject, $status = ProcessStatusCode::IN_PROGRESS)
    {
        $newEmailStatus = new EmailStatus;
        $newEmailStatus->quote_type_id = $emailData->quoteTypeId;
        $newEmailStatus->quote_id = $emailData->quoteId;
        $newEmailStatus->email_address = $emailData->customerEmail;
        $newEmailStatus->msg_id = $messageId;
        $newEmailStatus->template_id = $emailData->templateId;
        $newEmailStatus->customer_id = $emailData->customerId;
        $newEmailStatus->email_status = $status ?? ProcessStatusCode::IN_PROGRESS;
        $newEmailStatus->email_subject = $emailSubject;
        $newEmailStatus->save();

        return $newEmailStatus->id;
    }

    public function addBirdEmailStatus($request)
    {

        switch (request('quoteTypeId')) {
            case QuoteTypeId::Car:
                $quote = CarQuote::where('uuid', $request->uuid)->first();
                break;
            case QuoteTypeId::Health:
                $quote = HealthQuote::where('uuid', $request->uuid)->first();
                break;
            default:
                $quote = null;
                break;
        }
        if (! $quote) {
            info("lead not found for uuid: {$request->uuid} time: ".now());

            return (object) ['message' => 'lead not found', 'status' => false];
        }
        if (! EmailStatus::where('email_status', ProcessStatusCode::SENT)
            ->where('msg_id', $request->message_id)
            ->where('quote_id', $quote->id)->exists()) {
            $request->quoteId = $quote->id;
            $request->customerEmail = $request->customer_email;
            $this->addEmailStatus($request, $request->message_id, $request->subject, ProcessStatusCode::SENT);

            return (object) ['message' => 'Email event logged successfully', 'status' => true];
        } else {
            return (object) ['message' => 'Email event already logged', 'status' => true];
        }
    }

}
