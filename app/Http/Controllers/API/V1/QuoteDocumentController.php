<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteQuoteDocumentRequest;
use App\Http\Requests\QuoteDocumentRequest;
use App\Http\Resources\DocumentTypeResource;
use App\Http\Resources\QuoteDocumentResource;
use App\Services\ActivitiesService;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;

class QuoteDocumentController extends Controller
{
    use GenericQueriesAllLobs;

    protected $quoteDocumentService;

    public function __construct(QuoteDocumentService $quoteDocumentService)
    {
        $this->quoteDocumentService = $quoteDocumentService;
    }

    /**
     * get list of active document types can be presented to customer to upload documents
     *
     * @param $quoteType
     * @param  ActivitiesService  $activitiesService
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getQuoteDocumentsToReceive($quoteType, ActivitiesService $activitiesService)
    {
        $quoteTypeId = $activitiesService->getQuoteTypeId($quoteType);
        $documentTypes = $this->quoteDocumentService->getQuoteDocumentsToReceive($quoteTypeId);

        return DocumentTypeResource::collection($documentTypes);
    }

    /**
     * upload document to azure first and then record in database
     *
     * @param $type
     * @param  QuoteDocumentRequest  $request
     * @param  QuoteDocumentService  $quoteDocumentService
     * @return \Illuminate\Http\JsonResponse
     */
    public function store($quoteType, QuoteDocumentRequest $request)
    {
        $quote = $this->getQuoteObject($quoteType, $request->quote_uuid);

        $document = $this->quoteDocumentService->uploadQuoteDocument($request->file('file'), $request->validated(), $quote);
        return new QuoteDocumentResource($document);
    }

    /**
     * delete quote document
     * @param $quoteType
     * @param DeleteQuoteDocumentRequest $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function destroy($quoteType, DeleteQuoteDocumentRequest $request)
    {
        return $this->quoteDocumentService->deleteQuoteDocument($quoteType, $request->validated());
    }
}
