<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
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
        UserService $userService)
    {
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

    public function getQuoteDocumentsForEmail($quoteType, $quoteUuId)
    {
        $quoteModel = $this->quoteModel($quoteType, $quoteUuId);

        $documents = $quoteModel->documents;

        return $documents;
    }

    public function sendPolicyDocument($quoteType, $quoteUuId)
    {
        $documents = $this->getQuoteDocumentsForEmail($quoteType, $quoteUuId);

        foreach ($documents as $document) {
            $documentType = DocumentType::where('code', $document->document_type_code)->first();

            if ($documentType->send_to_customer == 1) {
                $documentUrls[] = $document->doc_url;
            }
        }

        $emailTemplateId = (int) config('constants.SIB_SEND_QUOTE_POLICY_TEMPLATE_ID');
        $emailTemplateId = $emailTemplateId ? $emailTemplateId : 375;
        $quoteModel = $this->quoteModel($quoteType, $quoteUuId);
        $customer = $this->customerService->getCustomerById($quoteModel->customer_id);
        $advisor = $this->userService->getUserById($quoteModel->advisor_id);

        $emailData = [
            'customerName' => $customer->first_name.' '.$customer->last_name,
            'customerEmail' => $customer->email,
            'advisorName' => $advisor->name,
            'advisorLandlineNo' => $advisor->landline_no,
            'advisorMobileNo' => $advisor->mobile_no,
            'quoteCdbId' => $quoteModel->code,
            'documentUrls' => $documentUrls,
        ];

        //dd($emailData);

        $response = $this->sendEmailCustomerService->sendEmail($emailTemplateId, $emailData, 'policy-documents-'.$quoteType.'-quote');

        dd('response: '.$response);
        //echo '<pre>'; print_r($documentUrls); echo '</pre>';
    }

    public function quoteModel($quoteType, $quoteUuId)
    {
        $model = '\\App\\Models\\'.ucwords($quoteType).'Quote';

        return $model::where('uuid', $quoteUuId)->first();
    }
}
