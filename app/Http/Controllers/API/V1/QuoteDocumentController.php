<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteDocumentRequest;
use App\Http\Resources\DocumentTypeResource;
use App\Services\ActivitiesService;
use App\Services\QuoteDocumentService;

class QuoteDocumentController extends Controller
{
    protected $quoteDocumentService;

    public function __construct(QuoteDocumentService $quoteDocumentService)
    {
        $this->quoteDocumentService = $quoteDocumentService;
    }

    /**
     * get list of active document types can be presented to customer to upload documents
     *
     * @param $quoteType
     * @param ActivitiesService $activitiesService
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getQuoteDocumentsToReceive($quoteType, ActivitiesService $activitiesService)
    {
        $quoteTypeId    = $activitiesService->getQuoteTypeId($quoteType);
        $documentTypes  = $this->quoteDocumentService->getQuoteDocumentsToReceive($quoteTypeId);
        return DocumentTypeResource::collection($documentTypes);
    }

    /**
     * upload document to azure first and then record in database
     * @param $type
     * @param QuoteDocumentRequest $request
     * @param QuoteDocumentService $quoteDocumentService
     * @return \Illuminate\Http\JsonResponse
     */
    public function store($type, QuoteDocumentRequest $request)
    {
        $model          = '\\App\\Models\\'.ucwords($type).'Quote';
        $quote          = $model::where('uuid', $request->quote_uuid)->first();

        //$request->document_type_code, $request->quote_uuid, $quote
        $response = $this->quoteDocumentService->uploadQuoteDocument($request->file('file'), $request->validated(), $quote);

        return $response;
    }
}
