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
     * return list of quote documents.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index($quoteType, $quoteUuid)
    {
        if ($quote = $this->getQuoteObject($quoteType, $quoteUuid)) {
            return QuoteDocumentResource::collection($quote->documents);
        }

        return response()->json(['message' => 'Quote not found.'], 404);
    }

    /**
     * get list of active document types can be presented to customer to upload documents.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getQuoteDocumentsToReceive($quoteType, ActivitiesService $activitiesService)
    {
        $quoteTypeId = $activitiesService->getQuoteTypeId($quoteType);
        $documentTypes = $this->quoteDocumentService->getQuoteDocumentsToReceive($quoteTypeId);

        return DocumentTypeResource::collection($documentTypes);
    }

    /**
     * upload document to azure first and then record in database.
     *
     * @param$type
     *
     * @param  QuoteDocumentService  $quoteDocumentService
     * @return \Illuminate\Http\JsonResponse
     */
    public function store($quoteType, QuoteDocumentRequest $request)
    {
        $quote = $this->getQuoteObject($quoteType, $request->quote_uuid);

        $document = $this->quoteDocumentService->uploadQuoteDocument(data_get($request, 'is_base_64', 0) == 1 ? $request->file : $request->file('file'), $request->validated(), $quote);

        return new QuoteDocumentResource($document);
    }

    /**
     * delete quote document.
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function destroy($quoteType, DeleteQuoteDocumentRequest $request)
    {
        return $this->quoteDocumentService->deleteQuoteDocument($quoteType, $request->validated());
    }
}
