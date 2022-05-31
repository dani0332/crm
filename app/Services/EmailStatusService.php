<?php

namespace App\Services;

use App\Models\EmailStatus;

class EmailStatusService extends BaseService
{
	public static function getEmailStatus($quoteTypeId, $quoteId)
	{
		return EmailStatus::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
        ->orderBy('updated_at', 'desc')
        ->get();
	}

}
