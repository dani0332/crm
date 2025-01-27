<?php

namespace App\Http\Requests;

use App\Enums\QuoteStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class LifeCardLoadMoreRequest extends FormRequest
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
            'status' => 'required|in:'.QuoteStatusEnum::NewLead.','.QuoteStatusEnum::Quoted.','.QuoteStatusEnum::FollowedUp.','.QuoteStatusEnum::InNegotiation,
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Quote Status is requered.',
            'status.in' => 'Quote Status should be one of '.QuoteStatusEnum::NewLead.','.QuoteStatusEnum::Quoted.','.QuoteStatusEnum::FollowedUp.','.QuoteStatusEnum::InNegotiation,
        ];
    }
}
