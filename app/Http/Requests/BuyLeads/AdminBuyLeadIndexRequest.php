<?php

namespace App\Http\Requests\BuyLeads;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminBuyLeadIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can(PermissionsEnum::BUY_LEADS_ADMIN);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'quote_type' => ['nullable', Rule::enum(QuoteTypes::class)],
            'status' => 'nullable|in:active,completed,processing,expired',
            'request_type' => 'nullable|in:value,volume',
            'date' => 'nullable|array|size:2',
            'date.*' => 'nullable|date',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user',
            'quote_type' => 'line of business',
            'request_type' => 'request type',
            'date' => 'date range',
        ];
    }
}
