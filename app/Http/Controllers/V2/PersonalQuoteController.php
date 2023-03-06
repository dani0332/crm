<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalQuoteDocumentRequest;
use App\Http\Requests\PersonalQuotePaymentRequest;
use App\Http\Requests\PersonalQuotePolicyRequest;
use App\Http\Requests\PersonalQuoteStatusRequest;
use App\Repositories\PersonalQuoteRepository;

class PersonalQuoteController extends Controller
{
    /**
     * @param $quoteType
     * @param $quoteId
     * @param  PersonalQuoteStatusRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus($quoteType, $quoteId, PersonalQuoteStatusRequest $request)
    {
        PersonalQuoteRepository::updateStatus($quoteType, $quoteId, $request->validated());

        return back()->with('message', 'Status updated successfully');
    }

    public function uploadDocument($quoteId, PersonalQuoteDocumentRequest $request)
    {
        PersonalQuoteRepository::uploadDocument($quoteId, request()->file('file'), $request->validated());

        return back()->with('message', 'Document uploaded successfully');
    }

    /**
     * @param  PersonalQuotePaymentRequest  $request
     * @return void
     */
    public function createPayment($quoteId, PersonalQuotePaymentRequest $request)
    {
        PersonalQuoteRepository::createPayment($quoteId, $request->validated());

        return back()->with('message', 'Payment created successfully');
    }

    /**
     * @param $quoteId
     * @param $paymentCode
     * @param  PersonalQuotePaymentRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePayment($quoteId, $paymentCode, PersonalQuotePaymentRequest $request)
    {
        PersonalQuoteRepository::updatePayment($quoteId, $paymentCode, $request->validated());

        return back()->with('message', 'Payment updated successfully');
    }

    public function updatePolicyDetails($id, PersonalQuotePolicyRequest $request)
    {
        $response = PersonalQuoteRepository::updatePolicyDetails($id, $request->validated());

        return back();
    }
}
