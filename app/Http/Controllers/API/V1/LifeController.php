<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LifeSendOCAEmailRequest;
use App\Jobs\SendOCAEmailJob;

class LifeController extends Controller
{
    public function sendOCAEmail(LifeSendOCAEmailRequest $request)
    {
        // Dispatch job to send OCA email
        SendOCAEmailJob::dispatch($request->quoteUID, $request->validated());

        return response()->json(['message' => 'OCA Email Job dispatched against UUID: LIFE-'.$request->quoteUID]);
    }
}
