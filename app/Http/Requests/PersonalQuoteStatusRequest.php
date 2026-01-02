<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
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
            'quote_uuid' => 'required',
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
            $quoteTypeId = QuoteTypes::getIdFromValue(request()->quote_type);
            $quoteObject = PersonalQuote::with([
                'latestInsured' => function ($query) use ($quoteTypeId) {
                    $query->where('customer_insured.quote_type_id', $quoteTypeId);
                },
                'customer',
            ])->where('uuid', request()->quote_uuid)->where('quote_type_id', $quoteTypeId)->firstOrFail();

            $customerProfileDetails = [
                'insured_first_name' => ($quoteObject?->latestInsured?->first_name ?? $quoteObject?->customer?->insured_first_name) ?? null,
                'insured_last_name' => ($quoteObject?->latestInsured?->last_name ?? $quoteObject?->customer?->insured_last_name) ?? null,
                'emirates_id_number' => ($quoteObject?->latestInsured?->id_type == 'emiratesId') ? $quoteObject?->latestInsured?->id_number : ($quoteObject?->customer?->emirates_id_number ?? null),
                'emirates_id_expiry_date' => $quoteObject?->customer?->emirates_id_expiry_date ?? null,
            ];

            if (request()->quote_status_id == QuoteStatusEnum::TransactionApproved) {
                $quoteTypeIds = [QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Home, QuoteTypeId::Jetski];
                if (in_array($quoteObject->quote_type_id, $quoteTypeIds) && method_exists($quoteObject, 'hasInsurerPaymentLink') && $quoteObject->hasInsurerPaymentLink() && ! $quoteObject->canUpdateToTransactionApproved() && ! auth()->user()->can(PermissionsEnum::SUPER_LEAD_STATUS_CHANGE)) {
                    $validator->errors()->add('value', 'Cannot update to Transaction Approved status. Quote must have payment initiated and payment link sent to customer.');
                }
            }

            if (in_array(null, $customerProfileDetails) && request()->quote_status_id == QuoteStatusEnum::TransactionApproved) {
                $validator->errors()->add('value', 'Please update customer profile information before moving to '.quoteStatusCode::TRANSACTIONAPPROVED.' status');
            }
        });
    }
}
