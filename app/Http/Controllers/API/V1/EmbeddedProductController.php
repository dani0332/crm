<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Repositories\EmbeddedProductRepository;
use Illuminate\Http\Response;

class EmbeddedProductController extends Controller
{
    public function sendDocument(EmbeddedProducDocumentRequest $request)
    {
        $data = $request->validated();
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];
        $epId = $data['epId'];
        $message = EmbeddedProductRepository::SendDocumentsByLead($quoteId, $modelType, $epId);

        return apiResponse(null, Response::HTTP_OK, '');
    }
}
