<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PermissionsEnum;
use Illuminate\Support\Facades\Auth;

class SplitPaymentApproveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'collection_amount' => 'array',
            'collection_amount.*' => 'nullable|numeric',
            'customer_id' => 'required|integer',
            'declined_custom_reason' => 'nullable|string',
            'declined_reason' => 'nullable|integer',
            'is_approved' => 'required|boolean',
            'is_capture' => 'required|boolean',
            'is_declined' => 'required|boolean',
            'modelType' => 'required|string',
            'plan_id' => 'required|integer',
            'quote_id' => 'required|integer',
            'collection_type' => 'required|string',
        ];
    }

    /**
     * validate quote record
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) { 
            // check if the user is authorized to approve the payment for broker           
            if (request()->is_approved === true && auth()->user()->cannot(PermissionsEnum::ApprovePayments)) {
                $validator->errors()->add('value', 'You are not authorized to approve this payment');
            }
        });
    }
}
