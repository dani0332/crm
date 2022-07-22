<?php

namespace App\Http\Controllers;

use App\Models\QuoteDocument;
use App\Models\TravelQuote;
use Illuminate\Http\Request;
use App\Services\CRUDService;
use App\Services\ActivitiesService;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Facades\Config;

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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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

    public function uploadDocumentProcess(Request $request)
    {
        if (!$request->hasFile('file')) {
            return false;
        }

        $travelQuote = TravelQuote::where('id', $request->quote_id)->first();
        $file = $request->file('file');
        $fileNameOriginal = $file->getClientOriginalName();
        $fileMimeType = $file->getClientMimeType();
        $fileNameAzure = uniqid().'_'.$fileNameOriginal;
        $filePathAzure = $request->file('file')->storeAs('documents/'.$request->folder_path, $fileNameAzure, 'azureIM');
        $this->quoteDocumentService->createQuoteDocumentRecord($request->document_type_code, $fileNameOriginal, $filePathAzure, $fileMimeType, $travelQuote);
    }
}
