<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Services\ActivitiesService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\QuoteDocumentService;
use App\Services\SendEmailCustomerService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuoteDocumentController extends Controller
{
    protected $crudService;
    protected $activityService;
    protected $quoteDocumentService;
    protected $sendEmailCustomerService;
    protected $customerService;
    protected $userService;

    public function __construct(
        CRUDService $crudService,
        ActivitiesService $activityService,
        QuoteDocumentService $quoteDocumentService,
        SendEmailCustomerService $sendEmailCustomerService,
        CustomerService $customerService,
        UserService $userService
    ) {
        $this->crudService = $crudService;
        $this->activityService = $activityService;
        $this->quoteDocumentService = $quoteDocumentService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->customerService = $customerService;
        $this->userService = $userService;
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

    public function list(Request $request, $quoteType, $quoteUuId)
    {
        $quoteModel = $this->crudService->quoteModel($quoteType, $quoteUuId);
        $quoteId = $quoteModel->id;
        $quoteCdbId = $quoteModel->code;
        $quoteTypeId = $this->activityService->getQuoteTypeId($quoteType);
        $documentUploadTypes = $this->quoteDocumentService->getQuoteDocumentsForUpload($quoteTypeId);

        return view('components.quote-documents-upload', compact(
            'quoteUuId',
            'quoteId',
            'quoteCdbId',
            'quoteType',
            'quoteTypeId',
            'documentUploadTypes'
        ));
    }

    public function store(Request $request, $quoteType)
    {
        $model = '\\App\\Models\\'.ucwords($quoteType).'Quote';
        $quoteModel = $model::where('id', $request->quote_id)->first();
        if (! $request->hasFile('file') || ! $quoteModel) {
            return false;
        }

        $file = $request->file('file');
        $fileNameOriginal = $file->getClientOriginalName();
        $fileMimeType = $file->getClientMimeType();
        $fileNameAzure = uniqid().'_'.$request->quote_uuid.'_'.$fileNameOriginal;
        $filePathAzure = $request->file('file')->storeAs('documents/'.$request->folder_path, $fileNameAzure, 'azureIM');
        $this->quoteDocumentService->createQuoteDocumentRecord($request->document_type_code, $fileNameOriginal, $filePathAzure, $fileMimeType, $quoteModel);
    }

    public function sendPolicyDocument($quoteType, $quoteUuId)
    {
        $emailTemplateId = (int) config('constants.SIB_TRAVEL_QUOTE_POLICY_TEMPLATE_ID');
        $quoteModel = $this->crudService->quoteModel($quoteType, $quoteUuId);
        $quoteDocuments = $this->getQuoteUploadedDocuments($quoteType, $quoteUuId);
        $policyWordingDocuments = $this->getPolicyWordingDocuments($quoteType, $quoteModel->plan_id);
        $documentUrl = array_merge($quoteDocuments, $policyWordingDocuments);
        $customer = $this->customerService->getCustomerById($quoteModel->customer_id);
        $advisor = $this->userService->getUserById($quoteModel->advisor_id);
        $quoteTypeId = $this->activityService->getQuoteTypeId($quoteType);

        $emailData = (object) [
            'customerName' => $customer->first_name.' '.$customer->last_name,
            'customerEmail' => $customer->email,
            'advisorName' => $advisor->name,
            'advisorLandlineNo' => $advisor->landline_no,
            'advisorMobileNo' => $advisor->mobile_no,
            'quoteCdbId' => $quoteModel->code,
            'quoteTypeId' => $quoteTypeId,
            'quoteId' => $quoteModel->id,
            'templateId' => $emailTemplateId,
            'customerId' => $customer->id,
            'documentUrl' => $documentUrl,
        ];

        $response = $this->sendEmailCustomerService->sendEmail($emailTemplateId, $emailData, 'policy-documents-'.$quoteType.'-quote');

        if ($response == 201) {
            return redirect()->back()->with('success', 'Quote Policy has been sent.');
        } else {
            return redirect()->back()->with('error', 'Quote Policy has not been sent. '.$response);
        }
    }

    public function getQuoteUploadedDocuments($quoteType, $quoteUuId)
    {
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');
        $quoteModel = $this->crudService->quoteModel($quoteType, $quoteUuId);

        $quoteDocumentUrls = [];
        foreach ($quoteModel->documents as $quoteDocument) {
            $documentType = DocumentType::where('code', $quoteDocument->document_type_code)->first();

            if ($documentType->send_to_customer == 1) {
                $quoteDocumentUrls[] = $azureStorageUrl.$azureStorageContainer.'/'.$quoteDocument->doc_url;
            }
        }

        return $quoteDocumentUrls;
    }

    public function getPolicyWordingDocuments($quoteType, $quotePlanId)
    {
        $model = '\\App\\Models\\'.ucwords($quoteType).'PlanPolicyWording';
        $policyWordingDocumentUrl = [];
        $policyWordingDocuments = $model::select('link')->where('plan_id', $quotePlanId)->get();
        foreach ($policyWordingDocuments as $policyWordingDocument) {
            $policyWordingDocumentUrl[] = $policyWordingDocument->link;
        }

        return $policyWordingDocumentUrl;
    }

    public function destroy($quoteType, $quoteUuId, $id)
    {
        $model = 'App\\Models\\'.ucwords($quoteType).'Quote';
        $document = QuoteDocument::where('id', $id)->where('quote_documentable_type', $model)->first();

        if (! $document) {
            return redirect()->back()->with('message', 'Document not found');
        }

        $document->delete();

        return redirect()->back()->with('message', 'Document has been deleted.');
    }
}
