<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\EmailServices\HomeEmailService;
use Illuminate\Http\Request;

class HomeQuoteController extends Controller
{
    public function homeRenewalOCBAttachment(Request $request)
    {
        $request->validate([
            'quoteUID' => 'required|string',
        ]);

        $publicUrl = app(HomeEmailService::class)->attachHomeOCBPDFToEmail($request->quoteUID);

        return response()->json([
            'public_url' => $publicUrl,
        ]);
    }
}
