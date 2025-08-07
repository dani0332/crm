<?php

namespace App\Http\Requests;

use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\Customer;
use App\Models\RenewalBatch;
use App\Services\AMLService;
use App\Services\TravelQuoteService;
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
            'current_quote_status_id' => [
                function ($attribute, $value, $fail) {

                    // Only validate for Car quotes
                    if (strtolower($this->input('modelType')) !== strtolower(quoteTypeCode::Car)) {
                        return;
                    }

                    // Check if lead status requires current_quote_status_id
                    $requiredStatuses = [
                        QuoteStatusEnum::PolicyIssued,          // 33
                        QuoteStatusEnum::PolicyCancelled,       // 58
                        QuoteStatusEnum::PolicyCancelledReissued // 74
                    ];

                    $leadStatus = (int)$this->input('leadStatus');
                    $isRequiredStatus = in_array($leadStatus, $requiredStatuses);
                    $isEmpty = empty($value);

                    // Convert leadStatus to int for comparison and check if field is required
                    if ($isRequiredStatus && $isEmpty) {
                        $fail('The current quote status id field is required when the lead status is Policy Issued, Policy Cancelled, or Policy Cancelled Reissued for Car quotes.');
                    }
                }
            ],
            'notes' => 'nullable',
            'lost_notes' => 'nullable|max:500',
            'approve_reason_id' => 'nullable',
            'reject_reason_id' => 'nullable',
        ];

        /*
         * advisor can mark car quote lead status to car sold / un-contactable with proof document required
         * && auth()->user()->hasRole(RolesEnum::CarAdvisor)
         */
        if (
            ! empty(request()->leadStatus) && ! empty(request()->modelType) && strtolower(request()->modelType) == strtolower(quoteTypeCode::Car)
            && isCarLostStatus(request()->leadStatus)
        ) {
            // check for valid quote
            if (! $quote = $this->getQuoteObject(request()->modelType, request()->quote_uuid)) {
                vAbort('Invalid quote type or uuid provided');
            }

            // once quote is marked as sold/uncontactable, quote should be locked until have pending request
            if (isCarLostStatus($quote->quote_status_id) && auth()->user()->hasAnyrole([RolesEnum::CarAdvisor])) {
                $quote->load('carLostQuoteLog');
                if (isset($quote->carLostQuoteLog->id) && $quote->carLostQuoteLog->status == GenericRequestEnum::PENDING) {
                    vAbort('Quote is locked as it has pending request to verify proof document');
                }
            }

            // check for deadline date
            $batch = RenewalBatch::where([
                'name' => $quote->renewal_batch,
            ])->with('deadline', function ($q) {
                $q->where('quote_status_id', request()->leadStatus);
            })->first();

            if (
                auth()->user()->hasAnyRole([RolesEnum::CarAdvisor]) &&
                isset($batch->deadline)
            ) {
                if (now()->gt(($batch->deadline->deadline_date . ' 23:59:59'))) {
                    vAbort('Not possible to select the lead status after the deadline has passed.');
                }
            }

            if (auth()->user()->hasAnyRole([RolesEnum::CarAdvisor])) {
                $rules['proof_document'] = 'required';
            }

            // todo: lost_approval_status should be required, and can be approved or rejected also reason_id should be required
            if (auth()->user()->hasRole(RolesEnum::MarketingOperations)) {
                $rules['lost_approval_status'] = 'required|in:' . GenericRequestEnum::APPROVED . ',' . GenericRequestEnum::REJECTED;
                $rules['approve_reason_id'] = 'required_without:reject_reason_id';
                $rules['reject_reason_id'] = 'required_without:approve_reason_id';
            }
        }

        if (request()->leadStatus == QuoteStatusEnum::Lost) {
            $rules['lostReason'] = 'required';
        }

        if (strtolower(request()->modelType) == strtolower(quoteTypeCode::Car)) {
            if (in_array(request()->leadStatus, [QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer])) {
                $rules['next_followup_date'] = 'required|date_format:' . config('constants.DATETIME_DISPLAY_FORMAT') . '|after_or_equal:' . date(config('constants.DATETIME_DISPLAY_FORMAT'));
                $rules['notes'] = 'required';
            }

            if (request()->leadStatus == QuoteStatusEnum::IMRenewal) {
                if (! isset(request()->tier_id)) {
                    $rules['tier_id'] = 'required';
                }
            }
        }

        return $rules;
    }

    /**
     * validate quote record and maximum number of alread uploaded files.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteTypesIds = QuoteTypeId::asArray();
            $quoteObject = $this->getQuoteObject(strtolower(request()->modelType), request()->leadId);

            if (! $quoteObject) {
                $validator->errors()->add('value', 'Lead not found please try again.');
            }

            if (! auth()->user()->can(PermissionsEnum::SUPER_LEAD_STATUS_CHANGE) && $quoteObject->quote_status_id == QuoteStatusEnum::Lost) {
                $validator->errors()->add('value', 'The lead is marked as ' . quoteStatusCode::LOST . ' and cannot be changed.');
            }

            $isTravelLeadTransactionApproved = false;
            $fetchLastAMLCheck = app(AMLService::class)->getLatestScreening(request()->leadId, $quoteTypesIds[request()->modelType]);

            if ((auth()->user()->hasPermissionTo(PermissionsEnum::TRAVEL_HAPEX) && strtolower(request()->modelType) === strtolower(quoteTypeCode::Travel))) {
                $transactionApprovedQuoteStatus = app(TravelQuoteService::class)->getTransactionApprovedQuoteStatus(request()->leadId);
                if (isset($transactionApprovedQuoteStatus->id)) {
                    $isTravelLeadTransactionApproved = true;
                }
            }

            if (isset($fetchLastAMLCheck->search_type) && substr($fetchLastAMLCheck->customer_code, 0, 3) == CustomerTypeEnum::IndividualShort && $isTravelLeadTransactionApproved == false) {

                $customer = Customer::with(['latestInsured' => function ($query) use ($quoteTypesIds) {
                    $query->where('quote_request_id', request()->leadId)
                        ->where('quote_type_id', $quoteTypesIds[request()->modelType]);
                }])->where('id', $quoteObject->customer_id)->first();

                $customerProfileDetails = [
                    'insured_first_name' => ($customer?->latestInsured?->first_name ?? $customer->insured_first_name) ?? null,
                    'insured_last_name' => ($customer?->latestInsured?->last_name ?? $customer->insured_last_name) ?? null,
                    'emirates_id_number' => ($customer?->latestInsured?->id_type == 'emiratesId') ? $customer?->latestInsured?->id_number : ($customer->emirates_id_number ?? null),
                    'emirates_id_expiry_date' => $customer->emirates_id_expiry_date ?? null,
                ];

                if (in_array(null, $customerProfileDetails) && request()->leadStatus == QuoteStatusEnum::TransactionApproved) {
                    $validator->errors()->add('value', 'Please update customer profile information before moving to ' . quoteStatusCode::TRANSACTIONAPPROVED . ' status');
                }
            }

            if (AMLService::checkAMLStatusFailed($quoteTypesIds[request()->modelType], request()->leadId) && request()->leadStatus == QuoteStatusEnum::TransactionApproved && $isTravelLeadTransactionApproved == false) {
                $validator->errors()->add('value', 'Error Approving, AML Status is not Passed');
            }

            if (strtolower(request()->modelType) == strtolower(quoteTypeCode::Health)) {
                if (($quoteObject->health_team_type == null || $quoteObject->health_team_type == quoteTypeCode::WCU) &&
                    request()->leadStatus == QuoteStatusEnum::Qualified
                ) {
                    $validator->errors()->add('value', 'Please select team type before moving to ' . quoteStatusCode::QUALIFIED . ' status');
                }
            }
        });
    }

    public function messages()
    {
        return [
            'proof_document.required' => 'In order to change the status to Car Sold or Uncontactable, a proof document is required',
            'leadStatus.required' => 'Please select lead status and try again.',
        ];
    }
}
