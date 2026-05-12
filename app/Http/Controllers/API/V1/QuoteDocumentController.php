<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteQuoteDocumentRequest;
use App\Http\Requests\QuoteDocumentRequest;
use App\Http\Requests\UploadToMetLifeRequest;
use App\Http\Resources\DocumentTypeResource;
use App\Http\Resources\QuoteDocumentResource;
use App\Services\ActivitiesService;
use App\Services\Logger\LoggerService;
use App\Services\MetLife\MetLifeApiService;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
     * @return JsonResponse|AnonymousResourceCollection
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
     * @return AnonymousResourceCollection
     */
    public function getQuoteDocumentsToReceive(Request $request, $quoteType, ActivitiesService $activitiesService)
    {
        $documentTypeCategory = $request->category;
        $quoteTypeId = $activitiesService->getQuoteTypeId($quoteType);
        $registrationType = $request->input('registration_type');
        $vehicleUse = $request->input('vehicle_use');

        $documentTypes = $this->quoteDocumentService->getQuoteDocumentsToReceive($quoteTypeId, $registrationType, $vehicleUse, $documentTypeCategory);

        return DocumentTypeResource::collection($documentTypes);
    }

    /**
     * upload document to azure first and then record in database.
     *
     * @param$type
     *
     * @param  QuoteDocumentService  $quoteDocumentService
     * @return JsonResponse
     */
    public function store($quoteType, QuoteDocumentRequest $request)
    {
        LoggerService::startQuoteLogging($request->quote_uuid, LoggerFeatureEnum::API_QUOTE_DOCUMENT_UPLOAD);

        LoggerService::info('API request received to upload documents to Azure', [
            'quote_uuid' => $request->quote_uuid,
            'quote_type' => $quoteType,
            'document_type_code' => $request->document_type_code,
            'member_detail_id' => $request->member_detail_id,
        ]);

        $quote = $this->getQuoteObject($quoteType, $request->quote_uuid);

        $result = $this->quoteDocumentService->uploadQuoteDocument(data_get($request, 'is_base_64', 0) == 1 ? $request->file : $request->file('file'), $request->validated(), $quote);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        if ($result === false) {
            return response()->json(['error' => 'Document upload failed'], 500);
        }

        return (new QuoteDocumentResource($result))->response()->setStatusCode(201);
    }

    /**
     * delete quote document.
     *
     * @return JsonResponse|void
     */
    public function destroy($quoteType, DeleteQuoteDocumentRequest $request)
    {
        return $this->quoteDocumentService->deleteQuoteDocument($quoteType, $request->validated());
    }

    public function handleMetLife($quoteType, UploadToMetLifeRequest $request)
    {
        $metLifeApiService = new MetLifeApiService;

        if (! $metLifeApiService->isMetLifeEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'MetLife feature is not enabled right now',
            ], 403);
        }

        $validatedData = $request->validated();

        LoggerService::startQuoteLogging(QuoteTypes::LIFE->refId($validatedData['quote_uuid']));
        LoggerService::info('API request received to upload documents to MetLife');

        $quote = $this->getQuoteObject($quoteType, $validatedData['quote_uuid']);

        if (! $quote) {
            return response()->json([
                'success' => false,
                'message' => 'Quote not found',
            ], 404);
        }

        $result = $metLifeApiService->handleDocumentUpload($validatedData, $quote);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get claim documents grouped by quote type and insurance provider
     *
     * @return JsonResponse
     */
    public function getClaimDocuments()
    {
        $data = $this->quoteDocumentService->getClaimDocuments();

        return response()->json([
            'data' => $data,
        ]);
    }
}
