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
        $response = EmbeddedProductRepository::sendDocument($request->validated());
        $message = 'Certificate send Successfully';
        $responseCode = Response::HTTP_OK;
        if($response === false) {
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = 'unable to send Certificate';
        }

        return apiResponse(null, $responseCode, $message);
    }
}
