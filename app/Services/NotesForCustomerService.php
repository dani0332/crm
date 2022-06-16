<?php

namespace App\Services;

use App\Models\NotesForCustomer;

class NotesForCustomerService extends BaseService
{
	public static function getNotesForCustomer($quoteTypeId, $quoteId)
	{
		return NotesForCustomer::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
        ->orderBy('updated_at', 'desc')
        ->get();
	}

}
