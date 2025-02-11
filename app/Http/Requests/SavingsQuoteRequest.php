<?php

namespace App\Http\Requests;

use App\Enums\GenderEnum;
use App\Enums\PermissionsEnum;
use App\Enums\SavingsPurposeEnum;
use App\Models\CurrencyType;
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
            || Auth::user()->can(PermissionsEnum::SAVINGS_QUOTES_EDIT);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:20',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email',
            'mobile_no' => 'required|string',
            'dob' => 'required|date',
            'nationality_id' => ['required', Rule::exists(Nationality::class, 'id')],
            'gender' => ['required', Rule::enum(GenderEnum::class)],
            'marital_status_id' => ['required', Rule::exists(MartialStatus::class, 'id')],
            'tenure_of_savings' => 'required|string',
            'has_nicotine' => 'required|in:0,1',
            'purpose_of_savings' => ['required', Rule::enum(SavingsPurposeEnum::class)],
            'currency_id' => ['required', Rule::exists(CurrencyType::class, 'id')],
            'amount' => 'required|numeric|min:1',
            'investment_frequency' => 'required|in:regular,lumpsum',
            'additional_notes' => 'required|string',
        ];
    }
}
