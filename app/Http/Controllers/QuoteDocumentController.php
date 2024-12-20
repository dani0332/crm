<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\WorkflowTypeEnum;
use App\Http\Requests\PaymentDocumentRequest;
use App\Http\Requests\QuotesDocumentRequest;
use App\Models\CustomerMembers;
use App\Models\DocumentType;
use App\Models\MemberCategory;
use App\Models\QuoteDocument;
use App\Models\SendUpdateLog;
use App\Services\ActivitiesService;
use App\Services\ApplicationStorageService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\ExportDocumentService;
use App\Services\QuoteDocumentService;
use App\Services\SendEmailCustomerService;
use App\Services\SIBService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class QuoteDocumentController extends Controller
{
    use GenericQueriesAllLobs;

    protected $crudService;
    protected $activityService;
    protected $quoteDocumentService;
    protected $sendEmailCustomerService;
    protected $customerService;
    protected $userService;
    protected $exportDocumentService;
    protected $applicationStorageService;

    public function __construct(
        CRUDService $crudService,
        ActivitiesService $activityService,
        QuoteDocumentService $quoteDocumentService,
        SendEmailCustomerService $sendEmailCustomerService,
        CustomerService $customerService,
        UserService $userService,
        ExportDocumentService $exportDocumentService,
        ApplicationStorageService $applicationStorageService,
    ) {
        $this->middleware('permission:'.PermissionsEnum::ENABLE_PROFORMA_PDF_DOWNLOAD_BUTTON, ['only' => ['createProformaPaymentRequest', 'downloadProformaPaymentRequest']]);

        $this->crudService = $crudService;
        $this->activityService = $activityService;
        $this->quoteDocumentService = $quoteDocumentService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->customerService = $customerService;
        $this->userService = $userService;
        $this->exportDocumentService = $exportDocumentService;
        $this->applicationStorageService = $applicationStorageService;
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
        $quote = $this->crudService->quoteModel($quoteType, $quoteUuId);

        $quoteId = $quote->id;
        $quoteCdbId = $quote->code;
        $quoteTypeId = $this->activityService->getQuoteTypeId($quoteType);
        $documentTypes = $this->quoteDocumentService->getQuoteDocumentsForUpload($quoteTypeId);

        $load = ['documents'];
        $view = 'components.quote-documents-upload';

        if ($quoteType == strtolower(quoteTypeCode::Health)) {
            $load = array_merge($load, ['members.memberCategory', 'members.documents']);
            $view = 'components.health-quote-documents-upload';
        }

        $quote->load($load);

        return view($view, compact(
            'quote',
            'quoteUuId',
            'quoteId',
            'quoteCdbId',
            'quoteType',
            'quoteTypeId',
            'documentTypes'
        ));
    }

    public function store($quoteType, QuotesDocumentRequest $request)
    {
        if (
            ! $request->hasFile('file') ||
            ! ($quote = $this->getQuoteObject($quoteType, $request->quote_id))
        ) {
            return false;
        }

        $this->quoteDocumentService->uploadQuoteDocument($request->file('file'), $request->all(), $quote);

        // update quote status - production process
        app(CentralService::class)->updateQuoteInformation($quoteType, $request->quote_id);

        return redirect()->back()->with('success', 'File Uploaded');
    }

    public function storeMultiple(PaymentDocumentRequest $request, $quoteType)
    {
        if (
            ! count($request->file) ||
            ! ($quote = $this->getQuoteObject($quoteType, $request->quote_id))
        ) {
            return false;
        }
        if ($request->send_update_id) {
            $quote = SendUpdateLog::find($request->send_update_id);
        }
        foreach ($request->file as $file) {
            $this->quoteDocumentService->uploadQuoteDocument($file['file'], $request->all(), $quote);
        }

        return redirect()->back()->with('success', 'Document Uploaded Successfully');
    }

    public function sendPolicyDocument($quoteType, $quoteUuId)
    {
        $quoteModel = $this->crudService->quoteModel($quoteType, $quoteUuId);
        switch ($quoteType) {
            case 'car':
                $emailTemplateId = (int) $this->applicationStorageService->getValueByKey('SIB_CAR_SEND_POLICY_TEMPLATE_ID');

                break;
            case strtolower(quoteTypeCode::Health):
                $emailTemplateId = (int) $this->applicationStorageService->getValueByKey('SIB_HEALTH_SEND_POLICY_TEMPLATE_ID');
                break;
            default:
                $emailTemplateId = false;
                break;
        }

        $quotePlan = $quoteModel->plan;
        if (! $quotePlan || ! $quotePlan->insuranceProvider || ! $quotePlan->insuranceProvider->code) {
            $providerSupportNumber = false;
        } else {
            $providerSupportNumber = $this->applicationStorageService->getValueByKey(strtoupper($quotePlan->insuranceProvider->code).'_CUSTOMER_SUPPORT_NUMBER');
        }
        if ($emailTemplateId == false) {
            if (request()->ajax()) {
                return response()->json(['error' => 'Error sending Quote Policy. Email Template not configured.']);
            }

            return redirect()->back()->with('error', 'Error sending Quote Policy. Email Template not configured.');
        }

        if (! $providerSupportNumber) {
            if (request()->ajax()) {
                return response()->json(['error' => 'Error sending Quote Policy. Provider Support Number not configured.']);
            }

            return redirect()->back()->with('error', 'Error sending Quote Policy. Provider Support Number not configured.');
        }

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
            'providerSupportNumber' => $providerSupportNumber,
        ];

        $response = $this->sendEmailCustomerService->sendEmail($emailTemplateId, $emailData, 'policy-documents-'.$quoteType.'-quote');

        if ($response == 201) {
            $this->crudService->updateQuoteStatusbyModel($quoteModel, QuoteStatusEnum::PolicyIssued);
            if (request()->ajax()) {
                return response()->json(['success' => 'Quote Policy has been sent.']);
            }

            return redirect()->back()->with('success', 'Quote Policy has been sent.');
        } else {
            if (request()->ajax()) {
                return response()->json(['success' => 'Error sending Quote Policy.']);
            }

            return redirect()->back()->with('error', 'Error sending Quote Policy.');
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
                $quoteDocumentUrls[] = $azureStorageUrl.$azureStorageContainer.'/'.$quoteDocument->watermarked_doc_url ?? $quoteDocument->doc_url;
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
        $query = QuoteDocument::where(['quote_documentable_id' => $quoteId, 'document_type_code' => $documentCode]);

        if (! empty(request()->member_id)) {
            $query->where('member_detail_id', request()->member_id);
        }

        return $query->get();
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
    }

    /**
     * Create Proforma Payment Request PDF.
     */
    public function createProformaPaymentRequest(Request $request, $quoteType, $quote)
    {
        $response = $this->exportDocumentService->createProformaPaymentRequestPdf($quoteType, $quote, $request);

        if (isset($response['error'])) {
            return redirect()->back()->with('message', $response['error']);
        }

        return response()->json(['success' => true, 'proforma_request' => $response]);
    }

    /**
     * download Proforma Payment Request PDF.
     */
    public function downloadProformaPaymentRequest(QuoteDocument $quoteDocument)
    {
        $disk = Storage::disk('azureIM');

        if ($disk->exists($quoteDocument->doc_url)) {
            $contents = $disk->get($quoteDocument->doc_url);

            return response($contents)->header('content-type', $quoteDocument->doc_mime_type);
        } else {
            abort(404);
        }
    }

    public function validateDocumentsUpdate($quoteType, $quoteUuId, Request $request)
    {
        $quoteModel = $this->crudService->quoteModel($quoteType, $quoteUuId);
        $quoteModel->is_documents_valid = $request->is_documents_valid;
        $quoteModel->save();
        if ($request->is_documents_valid) {
            $this->stopHapexReminder($quoteModel);
        }

        return redirect()->back()->with('message', 'Document validity status update successfully.');
    }

    public function stopHapexReminder($quote)
    {
        SIBService::createWorkflowEvent(WorkflowTypeEnum::TRAVEL_HAPEX_STOP_EMAIL_REMINDER, $quote, null, $quote);

        return true;
    }

    public function downloadAllDocuments(Request $request)
    {
        if (! auth()->user()->can(PermissionsEnum::DOWNLOAD_ALL_DOCUMENTS)) {
            return response()->json(['message' => 'User Has No Permission to Download Documents.'], 403);
        }

        $quoteDocuments = $request->input('quoteDocuments');
        if (! is_array($quoteDocuments) || count($quoteDocuments) === 0) {
            return response()->json(['message' => 'No documents provided.'], 400);
        }

        $disk = Storage::disk('azureIM');
        $zipFileName = "{$request->quote['first_name']} {$request->quote['last_name']}_{$request->quote['code']}.zip";
        $zipFilePath = storage_path('temp/'.$zipFileName);
        $zip = new ZipArchive;

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'Could not create ZIP file.'], 500);
        }

        $processedDocuments = [];
        foreach ($quoteDocuments as $document) {
            $docUrl = $document['doc_url'];
            $originalName = $document['original_name'];
            $pathPrefix = '';

            if (! empty($document['member_detail_id'])) {
                $member = CustomerMembers::find($document['member_detail_id']);
                if ($member && isset($member->member_category_id)) {
                    $memberCategory = MemberCategory::find($member->member_category_id);
                    if ($member && $memberCategory) {
                        $pathPrefix = "{$member->first_name} {$member->last_name}_{$request->quote['code']}_{$memberCategory->text}/";
                    }
                }
            }

            if ($disk->exists($docUrl)) {
                try {
                    $contents = $disk->get($docUrl);
                    $zip->addFromString($pathPrefix.$originalName, $contents);
                    $processedDocuments[] = $originalName;
                } catch (\Exception $e) {
                    info("Error processing document: {$originalName} - ".$e->getMessage());
                }
            } else {
                info("Document does not exist: {$docUrl}");
            }
        }

        $zip->close();

        // Check if any documents were added to the ZIP
        if (count($processedDocuments) === 0) {
            return response()->json(['message' => 'No documents were added to the ZIP file.'], 400);
        }

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    public function getS3TempUrl(Request $request)
    {
        return $this->quoteDocumentService->getDocumentTempURL($request->docURL);
    }
}
