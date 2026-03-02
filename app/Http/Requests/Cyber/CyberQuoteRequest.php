<?php

namespace App\Http\Requests\Cyber;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Requests\CustomerAddressRequest;
use App\Models\Emirate;
use App\Models\Nationality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class CyberQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PermissionsEnum::CYBER_QUOTES_CREATE)
            || $this->user()->can(PermissionsEnum::CYBER_QUOTES_EDIT)
            || $this->user()->can(PermissionsEnum::VIEW_ALL_LEADS);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'modelType' => 'required|in:'.QuoteTypes::CYBER->value,
            'addressObj' => 'required|array',
            'addressObj.address_type' => 'nullable|string|max:50',
            'first_name' => 'required|between:1,20|regex:/^[a-zA-Z\s\-]+$/',
            'last_name' => 'required|between:1,50|regex:/^[a-zA-Z\s\-]+$/',
            'email' => 'required|email',
            'mobile_no' => 'required|string',
            'dob' => 'required|date',
            'nationality_id' => ['required', Rule::exists(Nationality::class, 'id')],
            'emirate_of_registration_id' => ['required', Rule::exists(Emirate::class, 'id')],
        ];

        $addressRules = CustomerAddressRequest::createFrom($this)->rules();
        if (! empty($addressRules)) {
            $rules = array_merge($rules, Arr::dot(['addressObj' => $addressRules]));
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'The first name may only contain letters, spaces, and hyphens.',
            'last_name.regex' => 'The last name may only contain letters, spaces, and hyphens.',
            'emirate_of_registration_id.required' => 'The emirate of residence field is required.',
            'emirate_of_registration_id.exists' => 'The selected emirate of residence is invalid.',
        ];
    }
}
