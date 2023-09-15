<?php

namespace App\Http\Requests;

use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\Customer;
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
            'leadStatus' => 'required'
        ];

        if ($this->leadStatus == QuoteStatusEnum::Lost) {
            $rules['lostReason'] = 'required';
        }

        if ($this->leadStatus == QuoteStatusEnum::TransactionApproved) {
            $rules['trans_code'] = 'required';
        }

        if (strtolower($this->modelType) == strtolower(quoteTypeCode::Car)) {
            if(in_array($this->leadStatus, [QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer])) {
                $rules['next_followup_date'] = 'required|date_format:'.config('constants.DATETIME_DISPLAY_FORMAT').'|after_or_equal:'.date(config('constants.DATETIME_DISPLAY_FORMAT'));
                $rules['notes'] = 'required';
            }

            if ($this->leadStatus == QuoteStatusEnum::IMRenewal) {
                if (!isset($this->tier_id)) {
                    $rules['tier_id'] = 'required';
                }
            }
        }

        return $rules;
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $quoteObject = $this->getQuoteObject(strtolower(request()->modelType), request()->leadId);

            if(!$quoteObject) {
                $validator->errors()->add('value', 'Lead not found please try again.');
            }

            $customerProfileDetails = Customer::where('id', $quoteObject->customer_id)->first([
                'insured_first_name',
                'insured_last_name',
                'emirates_id_number',
                'emirates_id_expiry_date'
            ])->toArray();

            if (in_array(null, $customerProfileDetails) && request()->leadStatus == QuoteStatusEnum::TransactionApproved) {
                $validator->errors()->add('value', 'Please update customer profile information before moving to '.quoteStatusCode::TRANSACTIONAPPROVED.' status');
            }

            if (strtolower(request()->modelType) == strtolower(quoteTypeCode::Health)) {
                if( ($quoteObject->health_team_type == null || $quoteObject->health_team_type == quoteTypeCode::WCU) &&
                    request()->leadStatus == QuoteStatusEnum::Qualified) {
                    $validator->errors()->add('value', 'Please select team type before moving to '.quoteStatusCode::QUALIFIED.' status');
                }
            }
        });
    }

    /**
     * Get the validation rule messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'leadStatus.required' => 'Please select lead status and try again.',
        ];
    }
}
