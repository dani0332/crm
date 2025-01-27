<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePrimaryContactRequest;
use App\Http\Requests\PersonalQuotePaymentRequest;
use App\Http\Requests\PersonalQuotePolicyRequest;
use App\Http\Requests\PersonalQuoteStatusRequest;
use App\Http\Requests\QuotesDocumentRequest;
use App\Repositories\PersonalQuoteRepository;
use App\Services\CentralService;
use App\Services\CustomerService;
use App\Traits\GenericQueriesAllLobs;

class PersonalQuoteController extends Controller
{
    use GenericQueriesAllLobs;

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus($quoteType, $quoteId, PersonalQuoteStatusRequest $request)
    {
        $response = PersonalQuoteRepository::updateStatuses($quoteType, $quoteId, $request->validated());

        // Update payment allocation status when lead status changes when lead status as Policy Issue
        app(CentralService::class)->updatePaymentAllocation($quoteType, $quoteId);

        if (! $response['activity_created']) {
            return back()->with('message', 'Status updated successfully');
        }

        return back()->with('message', 'Status updated successfully & Activity has been created');
    }

    public function uploadDocument($quoteId, QuotesDocumentRequest $request)
    {
        $files = request()->file('files');
        $responses = collect();

        foreach ($files as $file) {
            info(' fn:'.__FUNCTION__.' Quote ID : '.$quoteId.' - Upload Document : '.$file->getClientOriginalName());

            $response = PersonalQuoteRepository::uploadDocument($quoteId, $file, $request->all());
            $responses->push($response); // collect all responses

            info(' fn:'.__FUNCTION__.' Quote ID : '.$quoteId.' - Upload Document : '.$file->getClientOriginalName().' - Status : '.$response['status'].' - message : '.$response['message']);
        }

        $hasErrors = $responses->where('status', false)->count();
        $errors = $responses->where('status', false)->pluck('message')->toArray();

        if ($hasErrors) {
            return back()->with('error', implode(', ', $errors));
        }

        app(CentralService::class)->updateQuoteInformation($request->folder_path, $quoteId);

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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePayment($quoteId, $paymentCode, PersonalQuotePaymentRequest $request)
    {
        PersonalQuoteRepository::updatePayment($quoteId, $paymentCode, $request->validated());

        return back()->with('message', 'Payment updated successfully');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePolicyDetails($id, PersonalQuotePolicyRequest $request)
    {
        $response = PersonalQuoteRepository::updatePolicyDetails($id, $request->validated());

        return back();
    }

    /**
     * @return mixed
     */
    public function getAuditHistory($quoteId)
    {
        return PersonalQuoteRepository::getAuditHistory($quoteId);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePrimaryContact($quoteId, ChangePrimaryContactRequest $request)
    {
        $quoteObject = PersonalQuoteRepository::findOrFail($quoteId);
        app(CustomerService::class)->makeAdditionalContactPrimary($quoteObject, $request->key, $request->value);

        return back();
    }
}
