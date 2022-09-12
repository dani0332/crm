<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteDocumentRequest;
use App\Models\DocumentType;
use App\Services\QuoteDocumentService;
use Illuminate\Http\Request;

class QuoteDocumentController extends Controller
{
    public function __construct()
    {

    }

    /**
     * upload document to azure first and then record in database
     * @param $type
     * @param QuoteDocumentRequest $request
     * @param QuoteDocumentService $quoteDocumentService
     * @return \Illuminate\Http\JsonResponse
     */
    public function store($type, QuoteDocumentRequest $request, QuoteDocumentService $quoteDocumentService)
    {
        try
        {
            $model          = '\\App\\Models\\'.ucwords($type).'Quote';
            $quote          = $model::where('uuid', $request->uuid)->first();
            $documentType   = DocumentType::where('code', $request->document_type_code)->first();
            $document       = $request->file('document');

            $fileNameOriginal   = $quoteDocumentService->createFileName($document);
            $fileNameAzure      = $quoteDocumentService->createAzureFileName($request->uuid, $fileNameOriginal);

            $filePathAzure      = $document->storeAs('documents/' . $documentType->folder_path, $fileNameAzure, 'azureIM');

            $quoteDocumentService->createQuoteDocumentRecord($request->document_type_code, $fileNameOriginal, $filePathAzure, $document->getClientMimeType(), $quote);

            return response()->json(['message'  => 'file uploaded successfully.']);
        }
        catch (\Exception $exception)
        {
            return response()->json(['error'  => 'Document upload failed, please try again'], 500);
        }

    }
}
