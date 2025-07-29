<?php

declare(strict_types=1);

namespace App\Http\Requests\V2\Admin\PrivateClientConfig;

use App\Enums\QuoteTypes;
use App\Models\QuoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrivateClientConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_type_id' => ['required', 'integer', Rule::exists(QuoteType::class, 'id')],
            'quote_type' => ['required', Rule::enum(QuoteTypes::class)],
            'config' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'quote_type_id.required' => 'Quote type is required.',
            'quote_type_id.integer' => 'Quote type must be a valid number.',
            'quote_type_id.exists' => 'The selected quote type does not exist.',

            'quote_type.required' => 'Quote type code is required.',
            'quote_type.enum' => 'The selected quote type is invalid.',

            'config.required' => 'Configuration data is required.',
            'config.array' => 'Configuration must be a valid structure.',
        ];
    }
}
