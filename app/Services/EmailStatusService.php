<?php

namespace App\Services;

use App\Enums\ProcessStatusCode;
use App\Models\EmailStatus;

class EmailStatusService extends BaseService
{
    public function getEmailStatus($quoteTypeId, $quoteId)
    {
        return EmailStatus::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
        ->orderBy('updated_at', 'desc')
        ->get();
    }

    public function addEmailStatus($emailData, $messageId)
    {
        $newEmailStatus = new EmailStatus();
        $newEmailStatus->quote_type_id = $emailData['quoteTypeId'];
        $newEmailStatus->quote_id = $emailData['quoteId'];
        $newEmailStatus->email_address = $emailData['customerEmail'];
        $newEmailStatus->msg_id = $messageId;
        $newEmailStatus->template_id = $emailData['templateId'];
        $newEmailStatus->customer_id = $emailData['customerId'];
        $newEmailStatus->email_status = ProcessStatusCode::IN_PROGRESS;
        $newEmailStatus->save();

        return $newEmailStatus->id;
    }
}
