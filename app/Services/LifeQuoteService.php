<?php

namespace App\Services;
use App\Models\LifeQuote;
use Illuminate\Http\Request;

class LifeQuoteService extends BaseService
{

	public static function saveLifeQuote(Request $request)
	{
        $lifeQuote = new LifeQuote();
        $lifeQuote->first_name = $request->first_name;
        $lifeQuote->last_name = $request->lastName;
        $lifeQuote->email = strtolower($request->email);
        $lifeQuote->others_info = $request->others_info;
        $lifeQuote->save();
	}
}
