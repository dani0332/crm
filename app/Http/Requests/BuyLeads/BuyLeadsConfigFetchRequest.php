<?php

namespace App\Http\Requests\BuyLeads;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BuyLeadsConfigFetchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasAnyRole([RolesEnum::LeadPool, RolesEnum::SeniorManagement, RolesEnum::Engineering]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_type' => ['required', Rule::enum(QuoteTypes::class)],
            'department_id' => 'required|exists:departments,id',
        ];
    }

    public function getQuoteTypeId()
    {
        return QuoteTypes::from($this->quote_type)->id();
    }
}
