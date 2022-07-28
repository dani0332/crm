<?php

namespace App\Http\Controllers;

use App\Services\ActivitiesService;
use App\Services\CRUDService;
use App\Services\QuoteDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuoteDocumentController extends Controller
{
    protected $crudService;
    protected $activityService;
    protected $quoteDocumentService;

    public function __construct(
        CRUDService $crudService,
        ActivitiesService $activityService,
        QuoteDocumentService $quoteDocumentService)
    {
        $this->crudService = $crudService;
        $this->activityService = $activityService;
        $this->quoteDocumentService = $quoteDocumentService;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $document = $this->quoteDocumentService->getQuoteDocumentUrl($id);
        $disk = Storage::disk('azureIM');

        if ($disk->exists($document->doc_url)) {
            $contents = $disk->get($document->doc_url);

            return response($contents)->header('content-type', $document->doc_mime_type);
        } else {
            abort(404);
        }
    }

    public function listQuoteDocuments(Request $request, $quoteType, $quoteUuId)
    {
        $quoteModel = $this->crudService->quoteModel($quoteType, $quoteUuId);
        $quoteId = $quoteModel->id;
        $quoteCdbId = $quoteModel->code;
        $quoteTypeId = $this->activityService->getQuoteTypeId($quoteType);
        $documentUploadTypes = $this->quoteDocumentService->getQuoteDocumentsForUpload($quoteTypeId);

        return view('components.quote-documents-upload', compact('quoteUuId', 'quoteId', 'quoteCdbId',
            'quoteType', 'quoteTypeId', 'documentUploadTypes'));
    }
}
