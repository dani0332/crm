<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Models\RenewalBatch;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadStatusRequest extends FormRequest
{
    use GenericQueriesAllLobs;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [
            'modelType' => 'required',
            'quote_uuid' => 'required',
            'leadStatus' => 'required',
            'notes' => 'nullable',
            'lost_notes' => 'nullable|max:100',
            'approve_reason_id' => 'nullable',
            'reject_reason_id' => 'nullable',
        ];

        /**
         * advisor can mark car quote lead status to car sold / un-contactable with proof document required
         * && auth()->user()->hasRole(RolesEnum::CarAdvisor)
         */
        if (! empty(request()->leadStatus) && ! empty(request()->modelType) && strtolower(request()->modelType) == strtolower(quoteTypeCode::Car)
            && isCarLostStatus(request()->leadStatus)
        ) {
            //check for valid quote
            if (! $quote = $this->getQuoteObject(request()->modelType, request()->quote_uuid)) {
                vAbort('Invalid quote type or uuid provided');
            }

            //once quote is marked as sold/uncontactable, quote should be locked until have pending request
            if (isCarLostStatus($quote->quote_status_id) && auth()->user()->hasAnyrole([RolesEnum::CarAdvisor, RolesEnum::CarDeputyManager])) {
                $quote->load('carLostQuoteLog');
                if (isset($quote->carLostQuoteLog->id) && $quote->carLostQuoteLog->status == GenericRequestEnum::PENDING) {
                    vAbort('Quote is locked as it has pending request to verify proof document');
                }
            }

            //check for deadline date
            $batch = RenewalBatch::where([
                'name' => $quote->renewal_batch,
            ])->with('deadline', function($q) {
                $q->where('quote_status_id' , request()->leadStatus);
            })->first();

            if (auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::MarketingOperations, RolesEnum::CarDeputyManager]) &&
                isset($batch->deadline) ) {
                if ( now()->gt($batch->deadline->deadline_date)) {
                    vAbort('Not possible to select the lead status after the deadline has passed.');
                }
            }

            if (auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarDeputyManager])) {
                $rules['proof_document'] = 'required';
            }
        }

        return $rules;
    }

    public function messages()
    {
        return ['proof_document.required' => 'In order to change the status to Car Sold or Uncontactable, a proof document is required'];
    }
}
