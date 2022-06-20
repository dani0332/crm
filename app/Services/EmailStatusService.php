<?php

namespace App\Services;

use App\Enums\ProcessStatusCode;
use App\Models\EmailStatus;

class EmailStatusService extends BaseService
{
	public static function getEmailStatus($quoteTypeId, $quoteId)
	{
		return EmailStatus::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
        ->orderBy('updated_at', 'desc')
        ->get();
	}

	public static function addEmailStatus($quoteTypeId, $quoteId, $customerEmail, $messageId)
	{
		$newEmailStatus = new EmailStatus();
		$newEmailStatus->quote_type_id = $quoteTypeId;
		$newEmailStatus->quote_id = $quoteId;
		$newEmailStatus->email_address = $customerEmail;
		$newEmailStatus->msg_id = $messageId;
		$newEmailStatus->email_status = ProcessStatusCode::IN_PROGRESS;
		$newEmailStatus->save();

		return $newEmailStatus->id;
	}

}
