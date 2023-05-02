<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendOCBEmailJob;
use Illuminate\Http\Request;

class SendOCBEmailController extends Controller
{
    public function getQuoteForOCBEmail(Request $request)
    {
        $quoteUuid = $request->input('quoteUID');
        dispatch(new SendOCBEmailJob($quoteUuid));

        return [
            'quote_uuid' => $quoteUuid,
        ];
    }


}
