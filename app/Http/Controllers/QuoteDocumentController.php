<?php

namespace App\Http\Controllers;

use App\Models\QuoteDocument;
use App\Models\TravelQuote;
use Illuminate\Http\Request;
use App\Services\CRUDService;
use App\Services\ActivitiesService;
use App\Services\QuoteDocumentService;
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

        if($disk->exists($document->doc_url)) {
            $contents = $disk->get($document->doc_url);
            return response($contents)->header('content-type', $document->doc_mime_type);
        } else {
            abort(404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $document = QuoteDocument::find($id);
        $document->delete();

        return redirect()->back()->with('message', 'Document has been deleted.');
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

    public function store(Request $request)
    {
        $travelQuote = TravelQuote::where('id', $request->quote_id)->first();

        if (!$request->hasFile('file')) {
            return false;
        }

        $file = $request->file('file');
        $fileNameOriginal = $file->getClientOriginalName();
        $fileMimeType = $file->getClientMimeType();
        $fileNameAzure = uniqid().'_'.$request->quote_uuid.'_'.$fileNameOriginal;
        $filePathAzure = $request->file('file')->storeAs('documents/'.$request->folder_path, $fileNameAzure, 'azureIM');
        $this->quoteDocumentService->createQuoteDocumentRecord($request->document_type_code, $fileNameOriginal, $filePathAzure, $fileMimeType, $travelQuote);
    }
}
