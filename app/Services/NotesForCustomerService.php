<?php

namespace App\Services;

use App\Models\NotesForCustomer;
use Illuminate\Support\Facades\Auth;

class NotesForCustomerService extends BaseService
{
	public static function getNotesForCustomer($quoteTypeId, $quoteId)
	{
		return NotesForCustomer::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
        ->orderBy('updated_at', 'desc')
        ->get();
	}

	public static function AddNoteForCustomer($request)
	{
		$newNote = new NotesForCustomer();
		$newNote->quote_type_id = $request->quote_type_id;
		$newNote->quote_id = $request->quote_id;
		$newNote->description = $request->description;
		$newNote->created_by_id = Auth::user()->id;
		$newNote->save();

		return $newNote->id;
	}

}
