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
        $message = EmbeddedProductRepository::sendDocument($request->validated());
        return apiResponse(null, Response::HTTP_OK, $message);
    }
}
