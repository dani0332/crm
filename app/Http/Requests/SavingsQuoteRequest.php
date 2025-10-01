<?php

namespace App\Http\Requests;

use App\Enums\GenderEnum;
use App\Enums\PermissionsEnum;
use App\Models\CurrencyType;
use App\Models\Lookup;
use App\Models\MartialStatus;
use App\Models\Nationality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SavingsQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can(PermissionsEnum::SAVINGS_QUOTES_CREATE)
            || Auth::user()->can(PermissionsEnum::SAVINGS_QUOTES_EDIT)
            || Auth::user()->can(PermissionsEnum::VIEW_ALL_LEADS);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|between:1,20|regex:/^[a-zA-Z\s\-]+$/',
            'last_name' => 'required|between:1,50|regex:/^[a-zA-Z\s\-]+$/',
            'email' => 'required|email',
            'mobile_no' => 'required|string',
            'dob' => 'required|date',
            'nationality_id' => ['required', Rule::exists(Nationality::class, 'id')],
            'gender' => ['required', Rule::enum(GenderEnum::class)],
            'marital_status_id' => ['required', Rule::exists(MartialStatus::class, 'id')],
            'tenure_id' => ['required', Rule::exists(Lookup::class, 'id')],
            'purpose_id' => ['required', Rule::exists(Lookup::class, 'id')],
            'currency_id' => ['required', Rule::exists(CurrencyType::class, 'id')],
            'investment_amount' => 'required|numeric|min:1',
            'investment_frequency' => ['required', Rule::exists(Lookup::class, 'id')],
            'notes' => 'required|string',
            // Sub-source fields
            'sub_source_id' => ['nullable', Rule::exists(Lookup::class, 'id')],
            'sub_source_options_id' => ['nullable', Rule::exists(Lookup::class, 'id')],
            'primary_ref_id' => 'nullable|string|max:255',
            'partner_name' => 'nullable|string|max:255',
        ];
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
        ];
    }
}
