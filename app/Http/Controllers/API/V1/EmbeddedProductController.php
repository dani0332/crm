<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Jobs\EP\SendEPJob;
use Illuminate\Http\Response;

class EmbeddedProductController extends Controller
{
    public function sendDocument(EmbeddedProducDocumentRequest $request)
    {
        $data = $request->validated();
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];
        $epId = $data['epId'];
        SendEPJob::dispatch($quoteId, $modelType, $epId)->delay(now()->addSeconds(30));

        return apiResponse(null, Response::HTTP_OK, '');
    }
}
