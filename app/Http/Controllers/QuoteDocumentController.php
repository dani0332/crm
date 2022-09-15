<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatusEnum;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Services\ActivitiesService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\QuoteDocumentService;
use App\Services\SendEmailCustomerService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuoteDocumentController extends Controller
{
    use GenericQueriesAllLobs;

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
        $documents = $quoteModel->documents;

        return view('components.quote-documents-upload', compact(
            'quoteUuId',
            'quoteId',
            'quoteCdbId',
            'quoteType',
            'quoteTypeId',
            'documentUploadTypes',
            'documents'
        ));
    }

    public function store(Request $request, $quoteType)
    {
        if (! $request->hasFile('file') ||
            ! ($quote = $this->getQuoteObject($quoteType, $request->quote_id))
        ) {
            return false;
        }

        return $this->quoteDocumentService->uploadQuoteDocument($request->file('file'), $request->all(), $quote);
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
            $this->crudService->updateQuoteStatusbyModel($quoteModel, QuoteStatusEnum::PolicyIssued);

            return redirect()->back()->with('success', 'Quote Policy has been sent.');
        } else {
            return redirect()->back()->with('error', 'Error sending Quote Policy. '.$response);
        }
    }

    public function getQuoteUploadedDocuments($quoteType, $quoteUuId)
    {
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');
        $quoteModel = $this->crudService->quoteModel($quoteType, $quoteUuId);

        $quoteDocumentUrls = [];
        foreach ($quoteModel->documents as $quoteDocument) {
            $documentType = DocumentType::where('code', $quoteDocument->document_type_code)->where('is_active', 1)->first();

            if ($documentType && $documentType->send_to_customer == 1) {
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

    public function getQuoteDocumentsUploaded($quoteType, $quoteId, $documentCode)
    {
        return QuoteDocument::where(['quote_documentable_id' => $quoteId, 'document_type_code' => $documentCode])->get();
    }

    public function destroy(Request $request)
    {
        request()->validate([
            'docName' => 'required|string',
            'quoteId' => 'required|integer',
        ]);
        $document = QuoteDocument::where('doc_name', $request->docName)->where('quote_documentable_id', $request->quoteId)->first();
        if (! $document) {
            return redirect()->back()->with('message', 'Document not found');
        }
        $document->delete();

        return response()->json(['message' => 'Document has been deleted.']);
    }
}
