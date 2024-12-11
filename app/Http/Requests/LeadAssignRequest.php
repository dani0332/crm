<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\PersonalQuote;
use Illuminate\Foundation\Http\FormRequest;

class LeadAssignRequest extends FormRequest
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
        return [
            'assigned_advisor_id' => 'required|exists:App\Models\User,id',
            'assigned_lead_id' => 'required',
        ];
    }

    public function withValidator($validator)
    {

        $validator->after(function ($validator) {
            $leadsIds = array_map('intval', explode(',', request()->assigned_lead_id));
            $personalQuotes = [quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Pet, quoteTypeCode::Yacht, quoteTypeCode::Jetski];

            $model = (in_array(ucfirst(request()->modelType), $personalQuotes)) ?
                PersonalQuote::class : (ucfirst(request()->modelType).'Quote');

            /**
             * check if the lead status is transaction approved.
             */
            foreach ($leadsIds as $leadId) {
                $getQuoteLead = $model::find($leadId);
                if (! $getQuoteLead) {
                    $validator->errors()->add('assigned_lead_id', 'Manual Lead Assignment Failed for '.ucfirst(request()->modelType).', selected id was '.$leadId);
                    break;
                }

                if (isset($getQuoteLead->quote_status_id) && $getQuoteLead->quote_status_id == QuoteStatusEnum::TransactionApproved && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
                    $validator->errors()->add('assigned_lead_id', 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.');
                    break;
                }

            }
        });
    }

    public function messages()
    {
        return [
            'assigned_advisor_id.required' => 'Please select user to assign leads',
            'assigned_lead_id.required' => 'Please select lead(s) to assign',
        ];
    }
}
