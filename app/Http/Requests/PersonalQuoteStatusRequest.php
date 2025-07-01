<?php

namespace App\Http\Requests;

use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Models\PersonalQuote;
use Illuminate\Foundation\Http\FormRequest;

class PersonalQuoteStatusRequest extends FormRequest
{
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
        $data = request()->all();

        $rules = [
            'quote_status_id' => 'required',
            'notes' => 'nullable',
        ];

        if (! empty($data['quote_status_id'])) {
            if ($data['quote_status_id'] == QuoteStatusEnum::Lost) {
                $rules['lost_reason_id'] = 'required';
            }
        }

        return $rules;
    }

    /**
     * validate quote record and maximum number of already uploaded files
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteObject = PersonalQuote::where('uuid', request()->quote_uuid)->firstOrFail();
            // TODO:: Insured mapping verified

            $customer = Customer::with([
                'insured' => function ($query) {
                    $query->where('quote_request_id', request()->quoteId)
                        ->where('quote_type_id', QuoteTypes::getIdFromValue(request()->quoteType));
                },
            ])->where('id', $quoteObject->customer_id)->first();

            $customerProfileDetails = [
                'insured_first_name' => ($customer?->insured?->first_name ?? $customer->insured_first_name) ?? null,
                'insured_last_name' => ($customer?->insured?->last_name ?? $customer->insured_last_name) ?? null,
                'emirates_id_number' => ($customer?->insured?->id_type == 'emiratesId') ? $customer?->insured?->id_number : ($customer->emirates_id_number ?? null),
                'emirates_id_expiry_date' => $customer->emirates_id_expiry_date ?? null,
            ];

            if(request()->quote_status_id == QuoteStatusEnum::TransactionApproved) {
                $quoteTypeIds = [QuoteTypeId::Health, QuoteTypeId::Life, QuoteTypeId::Business, QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Jetski];
                if(in_array($quoteObject->quote_type_id, $quoteTypeIds) && method_exists($quoteObject, 'hasInsurerPaymentLink') && $quoteObject->hasInsurerPaymentLink() && ! $quoteObject->canUpdateToTransactionApproved() && ! auth()->user()->can(PermissionsEnum::SUPER_LEAD_STATUS_CHANGE)) {
                    $validator->errors()->add('value', 'Cannot update to Transaction Approved status. Quote must have payment initiated and payment link sent to customer.');
            }

            if (in_array(null, $customerProfileDetails) && request()->quote_status_id == QuoteStatusEnum::TransactionApproved) {
                $validator->errors()->add('value', 'Please update customer profile information before moving to '.quoteStatusCode::TRANSACTIONAPPROVED.' status');
            }
        });
    }
}
