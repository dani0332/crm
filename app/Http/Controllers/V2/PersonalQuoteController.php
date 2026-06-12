<?php

namespace App\Http\Controllers\V2;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\SLAActionTypeEnum;
use App\Enums\WorkflowTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePrimaryContactRequest;
use App\Http\Requests\PersonalQuotePaymentRequest;
use App\Http\Requests\PersonalQuotePolicyRequest;
use App\Http\Requests\PersonalQuoteStatusRequest;
use App\Http\Requests\QuotesDocumentRequest;
use App\Repositories\PersonalQuoteRepository;
use App\Services\CentralService;
use App\Services\CustomerService;
use App\Services\QuoteDocumentService;
use App\Services\SIBService;
use App\Services\SLA\SLAService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class PersonalQuoteController extends Controller
{
    use GenericQueriesAllLobs;

    /**
     * @return RedirectResponse
     */
    public function updateStatus($quoteType, $quoteId, PersonalQuoteStatusRequest $request)
    {
        $response = PersonalQuoteRepository::updateStatuses($quoteType, $quoteId, $request->validated());

        // Update payment allocation status when lead status changes when lead status as Policy Issue
        app(CentralService::class)->updatePaymentAllocation($quoteType, $request->quote_uuid);

        if (! $response['activity_created']) {
            return back()->with('message', 'Status updated successfully');
        }

        return back()->with('message', 'Status updated successfully & Activity has been created');
    }

    public function uploadDocument($quoteId, QuotesDocumentRequest $request)
    {
        $documentService = app(QuoteDocumentService::class);
        $files = request()->file('files');
        $responses = collect();

        foreach ($files as $file) {
            info(' fn:'.__FUNCTION__.' Quote ID : '.$quoteId.' - Upload Document : '.$file->getClientOriginalName());

            $response = PersonalQuoteRepository::uploadDocument($quoteId, $file, $request->all());
            $responses->push($response); // collect all responses

            info(' fn:'.__FUNCTION__.' Quote ID : '.$quoteId.' - Upload Document : '.$file->getClientOriginalName().' - Status : '.$response['status'].' - message : '.$response['message']);
        }

        if ($request->document_type_code === DocumentTypeCode::TRVLPAS) {
            $quote = $this->getQuoteObject(QuoteTypes::TRAVEL->value, $quoteId);
            $this->stopHapexReminder($quote);
        }

        $hasErrors = $responses->where('status', false)->count();
        $errors = $responses->where('status', false)->pluck('message')->toArray();

        if ($hasErrors) {
            return back()->with('error', implode(', ', $errors));
        }

        $quote = $this->getQuoteObject($request->quote_type, $quoteId);

        $docTypes = $documentService->bringProofDocumentForAllLobs();
        if (in_array($request->document_type_code, $docTypes)) {
            if (method_exists($quote, 'hasInsurerPaymentLink') && $quote->hasInsurerPaymentLink()) {
                $documentService->updateQuoteAndPaymentStatusToPaymentPending($quote);
            }
        }

        app(CentralService::class)->updateQuoteInformation($request->folder_path, $quoteId);

        if ($quote && $quote instanceof Model) {
            app(SLAService::class)->meetSLAOnEdit($quote, SLAActionTypeEnum::DOCUMENTS_UPLOAD);
        }

        return back()->with('message', 'All files uploaded successfully');
    }

    /**
     * @return void
     */
    public function createPayment($quoteId, PersonalQuotePaymentRequest $request)
    {
        PersonalQuoteRepository::createPayment($quoteId, $request->validated());

        return back()->with('message', 'Payment created successfully');
    }

    /**
     * @return RedirectResponse
     */
    public function updatePayment($quoteId, $paymentCode, PersonalQuotePaymentRequest $request)
    {
        PersonalQuoteRepository::updatePayment($quoteId, $paymentCode, $request->validated());

        return back()->with('message', 'Payment updated successfully');
    }

    /**
     * @return RedirectResponse
     */
    public function updatePolicyDetails($id, PersonalQuotePolicyRequest $request)
    {
        $response = PersonalQuoteRepository::updatePolicyDetails($id, $request->validated());

        return back();
    }

    /**
     * @return RedirectResponse
     */
    public function changePrimaryContact($quoteId, ChangePrimaryContactRequest $request)
    {
        $quoteObject = PersonalQuoteRepository::findOrFail($quoteId);

        $keepExistingPrimaryEmail = isset($request->keep_existing_primary_email) ? $request->keep_existing_primary_email : 1;
        app(CustomerService::class)->makeAdditionalContactPrimary($quoteObject, $request->key, $request->value, (bool) $keepExistingPrimaryEmail);

        return back();
    }

    public function stopHapexReminder($quote)
    {
        SIBService::createWorkflowEvent(WorkflowTypeEnum::TRAVEL_HAPEX_STOP_EMAIL_REMINDER, $quote, null, $quote);
        info(self::class.'- stopHapexReminder Hapex reminder stopped for Quote UUID: '.$quote->uuid.' | Time - '.now());

        return true;
    }
}
